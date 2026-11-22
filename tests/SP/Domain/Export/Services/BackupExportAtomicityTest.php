<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace SP\Tests\Domain\Export\Services;

use DOMElement;
use Generator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\FileException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Crypt\Adapters\HashServiceAdapter;
use SP\Domain\Database\Dtos\DatabaseTables;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Domain\Database\Ports\QueryDataInterface;
use SP\Domain\Export\Dtos\BackupFile as BackupFileDto;
use SP\Domain\Export\Dtos\BackupFiles;
use SP\Domain\Export\Dtos\BackupType;
use SP\Domain\Export\Ports\XmlAccountExportService;
use SP\Domain\Export\Ports\XmlCategoryExportService;
use SP\Domain\Export\Ports\XmlClientExportService;
use SP\Domain\Export\Ports\XmlTagExportService;
use SP\Domain\Export\Services\BackupFile;
use SP\Domain\Export\Services\XmlExport;
use SP\Domain\File\Ports\ArchiveHandlerInterface;
use SP\Domain\File\Ports\DirectoryHandlerService;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Infrastructure\Database\QueryFactoryAdapter;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Characterization tests for pre-completion destructive cleanup, partial artifact retention,
 * SQL dump edge cases, and premature hash persistence in BackupFile and XmlExport.
 */
#[Group('unitary')]
final class BackupExportAtomicityTest extends UnitaryTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-backup-atomicity-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    /**
     * Characterization: BackupFile::doBackup() calls deleteOldBackups() BEFORE backupTables()
     * or backupApp() executes. If DB schema lookup fails mid-backup, previously valid archives
     * are already deleted from disk, while backupHash remains unchanged.
     */
    #[Test]
    public function backupDeletesPreviousArchivesBeforeDbDumpAndLeavesNoneOnFailure(): void
    {
        $priorApp = $this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_app-priorhash123.tar.gz';
        $priorDb = $this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_db-priorhash123.tar.gz';
        file_put_contents($priorApp, 'prior-app-archive');
        file_put_contents($priorDb, 'prior-db-archive');

        $configData = (new ConfigData())
            ->setDbName('syspass_test')
            ->setBackupHash('priorhash123');

        $configFile = $this->createMock(ConfigFileService::class);
        $configFile->method('getConfigData')->willReturn($configData);
        $configFile->expects(self::never())->method('save');

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('doFetchWithOptions')->willReturn((static function (): Generator {
            yield from [];
        })());

        $dbFileHandler = $this->createMock(FileHandlerInterface::class);
        $dbFileHandler->method('write')->willReturnSelf();

        $backupFiles = new BackupFiles(
            new BackupFileDto(BackupType::app, 'newhash456', $this->tempDir, 'tar'),
            new BackupFileDto(BackupType::db, 'newhash456', $this->tempDir, 'sql')
        );

        $service = new BackupFile(
            $configFile,
            $this->eventDispatcherStub,
            $this->context,
            $database,
            $this->createStub(DatabaseUtilInterface::class),
            $dbFileHandler,
            $this->createStub(ArchiveHandlerInterface::class),
            $this->createStub(ArchiveHandlerInterface::class),
            $backupFiles,
            new QueryFactoryAdapter()
        );

        try {
            $service->doBackup($this->tempDir, APP_PATH);
            self::fail('Expected ServiceException on missing schema');
        } catch (ServiceException $e) {
            self::assertSame('Error while doing the backup', $e->getMessage());
        }

        // Prior valid backups were already unlinked before backupTables() failed
        self::assertFileDoesNotExist($priorApp);
        self::assertFileDoesNotExist($priorDb);
        self::assertSame('priorhash123', $configData->getBackupHash());
    }

    /**
     * Characterization: If DB backup succeeds (leaving a new DB archive) and app archiving throws,
     * prior backups are gone, the new DB archive stays orphaned on disk, and backupHash is not updated.
     */
    #[Test]
    public function backupLeavesOrphanDbArchiveWhenAppArchivingFailsAfterWipingPriorBackups(): void
    {
        $priorApp = $this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_app-priorhash123.tar.gz';
        $priorDb = $this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_db-priorhash123.tar.gz';
        file_put_contents($priorApp, 'prior-app-archive');
        file_put_contents($priorDb, 'prior-db-archive');

        $newDbGz = $this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_db-newhash456.tar.gz';

        $configData = (new ConfigData())
            ->setDbName('syspass_test')
            ->setBackupHash('priorhash123');

        $configFile = $this->createMock(ConfigFileService::class);
        $configFile->method('getConfigData')->willReturn($configData);
        $configFile->expects(self::never())->method('save');

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('doFetchWithOptions')->willReturnCallback(
            static function (QueryDataInterface $queryData): Generator {
                $stmt = $queryData->getQuery()->getStatement();

                if (preg_match('/^SHOW CREATE TABLE `([^`]+)`$/', $stmt, $matches) === 1) {
                    $name = $matches[1];

                    if (str_ends_with($name, '_v')) {
                        return (static function () use ($name): Generator {
                            yield ['View' => $name, 'Create View' => "CREATE VIEW `{$name}` AS SELECT 1"];
                        })();
                    }

                    return (static function () use ($name): Generator {
                        yield ['Table' => $name, 'Create Table' => "CREATE TABLE `{$name}` (`id` int)"];
                    })();
                }

                return (static function (): Generator {
                    yield from [];
                })();
            }
        );

        $dbFileHandler = $this->createMock(FileHandlerInterface::class);
        $dbFileHandler->method('write')->willReturnSelf();
        $dbFileHandler->method('getFile')->willReturn($this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_db-newhash456.sql');

        $dbArchiveHandler = $this->createMock(ArchiveHandlerInterface::class);
        $dbArchiveHandler->expects(self::once())
            ->method('compressFile')
            ->willReturnCallback(static function () use ($newDbGz): string {
                file_put_contents($newDbGz, 'new-db-archive');

                return $newDbGz;
            });

        $appArchiveHandler = $this->createMock(ArchiveHandlerInterface::class);
        $appArchiveHandler->expects(self::once())
            ->method('compressDirectory')
            ->willThrowException(FileException::error('Disk full during app archive'));

        $backupFiles = new BackupFiles(
            new BackupFileDto(BackupType::app, 'newhash456', $this->tempDir, 'tar'),
            new BackupFileDto(BackupType::db, 'newhash456', $this->tempDir, 'sql')
        );

        $service = new BackupFile(
            $configFile,
            $this->eventDispatcherStub,
            $this->context,
            $database,
            $this->createStub(DatabaseUtilInterface::class),
            $dbFileHandler,
            $dbArchiveHandler,
            $appArchiveHandler,
            $backupFiles,
            new QueryFactoryAdapter()
        );

        try {
            $service->doBackup($this->tempDir, APP_PATH);
            self::fail('Expected ServiceException when app archive fails');
        } catch (ServiceException $e) {
            self::assertSame('Error while doing the backup', $e->getMessage());
        }

        self::assertFileDoesNotExist($priorApp);
        self::assertFileDoesNotExist($priorDb);
        self::assertFileExists($newDbGz);
        self::assertSame('priorhash123', $configData->getBackupHash());
    }

    /**
     * Characterization: BackupFile::backupTables() maps falsy non-numeric column values ('', null, false)
     * to PHP null in array_map(), which implode(',') renders as empty SQL syntax `,,` instead of NULL or ''.
     */
    #[Test]
    public function backupTablesSerializesEmptyStringsAndNullAsEmptySqlTokens(): void
    {
        $configData = (new ConfigData())->setDbName('syspass_test');

        $configFile = $this->createStub(ConfigFileService::class);
        $configFile->method('getConfigData')->willReturn($configData);

        $written = [];
        $dbFileHandler = $this->createMock(FileHandlerInterface::class);
        $dbFileHandler->method('write')->willReturnCallback(
            static function (string $chunk) use (&$written, $dbFileHandler): FileHandlerInterface {
                $written[] = $chunk;

                return $dbFileHandler;
            }
        );
        $dbFileHandler->method('getFile')->willReturn($this->tempDir . DIRECTORY_SEPARATOR . 'dump.sql');

        $databaseUtil = $this->createStub(DatabaseUtilInterface::class);
        $databaseUtil->method('escape')->willReturnCallback(static fn(string $v): string => "'{$v}'");

        $tablesCount = count(DatabaseTables::TABLES);
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects(self::exactly($tablesCount * 2 - 2))
            ->method('doFetchWithOptions')
            ->willReturnCallback(static function (QueryDataInterface $queryData): Generator {
                $stmt = $queryData->getQuery()->getStatement();

                if (preg_match('/^SHOW CREATE TABLE `([^`]+)`$/', $stmt, $matches) === 1) {
                    $name = $matches[1];

                    return str_ends_with($name, '_v')
                        ? (static function () use ($name): Generator {
                            yield ['View' => $name, 'Create View' => "CREATE VIEW `{$name}` AS SELECT 1"];
                        })()
                        : (static function () use ($name): Generator {
                            yield ['Table' => $name, 'Create Table' => "CREATE TABLE `{$name}` (`id` int)"];
                        })();
                }

                if ($stmt === 'SELECT * FROM `Account`') {
                    return (static function (): Generator {
                        yield [1, '', null, 'ok'];
                    })();
                }

                return (static function (): Generator {
                    yield from [];
                })();
            });

        $backupFiles = new BackupFiles(
            new BackupFileDto(BackupType::app, 'h1', $this->tempDir, 'tar'),
            new BackupFileDto(BackupType::db, 'h1', $this->tempDir, 'sql')
        );

        $service = new BackupFile(
            $configFile,
            $this->eventDispatcherStub,
            $this->context,
            $database,
            $databaseUtil,
            $dbFileHandler,
            $this->createStub(ArchiveHandlerInterface::class),
            $this->createStub(ArchiveHandlerInterface::class),
            $backupFiles,
            new QueryFactoryAdapter()
        );

        $service->doBackup($this->tempDir, APP_PATH);

        $sql = implode('', $written);
        self::assertStringContainsString("INSERT INTO `Account` VALUES(1,,,'ok');", $sql);
    }

    /**
     * Characterization: XmlExport::export() deletes previous export files AND saves the new exportHash
     * into ConfigFileService BEFORE exporting nodes or writing the XML file. If export fails midway,
     * prior exports are destroyed and config.exportHash points to a non-existent archive.
     */
    #[Test]
    public function xmlExportDeletesPreviousExportsAndPersistsNewHashBeforeBuildingXml(): void
    {
        $this->context->setUserData(
            UserDto::fromModel(
                UserDataGenerator::factory()
                    ->buildUserData()
                    ->mutate([
                        'login' => 'export_user',
                        'userGroupName' => 'Admins',
                    ])
            )
        );

        $priorExportGz = $this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_export-oldhash999.tar.gz';
        $priorExportXml = $this->tempDir . DIRECTORY_SEPARATOR . 'sysPass_export-oldhash999.xml';
        file_put_contents($priorExportGz, 'prior-export-gz');
        file_put_contents($priorExportXml, '<Root/>');

        $savedHashes = [];
        $configData = (new ConfigData())->setExportHash('oldhash999');

        $configFile = $this->createMock(ConfigFileService::class);
        $configFile->method('getConfigData')->willReturn($configData);
        $configFile->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (ConfigDataInterface $data) use (&$savedHashes, $configFile): ConfigFileService {
                $savedHashes[] = $data->getExportHash();

                return $configFile;
            });

        $dirHandler = $this->createMock(DirectoryHandlerService::class);
        $dirHandler->expects(self::once())->method('checkOrCreate');
        $dirHandler->method('getPath')->willReturn($this->tempDir);

        $categoryExport = $this->createStub(XmlCategoryExportService::class);
        $categoryExport->method('export')->willReturn(new DOMElement('Categories'));

        $clientExport = $this->createStub(XmlClientExportService::class);
        $clientExport->method('export')->willReturn(new DOMElement('Clients'));

        $tagExport = $this->createStub(XmlTagExportService::class);
        $tagExport->method('export')->willReturn(new DOMElement('Tags'));

        $accountExport = $this->createMock(XmlAccountExportService::class);
        $accountExport->expects(self::once())
            ->method('export')
            ->willThrowException(new RuntimeException('Account export interrupted'));

        $xmlExport = new XmlExport(
            $configFile,
            $this->eventDispatcherStub,
            $this->context,
            $this->createStub(PhpExtensionCheckerService::class),
            $clientExport,
            $accountExport,
            $categoryExport,
            $tagExport,
            $this->createStub(CryptInterface::class),
            new HashServiceAdapter()
        );

        try {
            $xmlExport->export($dirHandler);
            self::fail('Expected ServiceException on account export failure');
        } catch (ServiceException $e) {
            self::assertSame('Error while exporting', $e->getMessage());
        }

        // Old exports were deleted and new hash was persisted before Account export failed
        self::assertFileDoesNotExist($priorExportGz);
        self::assertFileDoesNotExist($priorExportXml);
        self::assertCount(1, $savedHashes);
        self::assertNotSame('oldhash999', $savedHashes[0]);
        self::assertSame($savedHashes[0], $configData->getExportHash());
        self::assertSame([], glob($this->tempDir . DIRECTORY_SEPARATOR . '*') ?: []);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
