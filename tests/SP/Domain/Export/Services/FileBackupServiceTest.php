<?php

declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Domain\Export\Services;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Bootstrap\Path;
use SP\Domain\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Dtos\DatabaseTables;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Domain\Export\Dtos\BackupFile as BackupFileDto;
use SP\Domain\Export\Dtos\BackupFiles;
use SP\Domain\Export\Dtos\BackupType;
use SP\Domain\Export\Services\BackupFile;
use SP\Domain\File\Ports\ArchiveHandlerInterface;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Database\Ports\QueryDataInterface;
use SP\Domain\Core\Exceptions\FileException;
use SP\Tests\UnitaryTestCase;
use Generator;

/**
 * Class FileBackupServiceTest
 *
 */
#[Group('unitary')]
class FileBackupServiceTest extends UnitaryTestCase
{
    private BackupFile                         $fileBackupService;
    private BackupFiles $backupFiles;
    private DatabaseInterface|MockObject       $database;
    private MockObject|FileHandlerInterface    $dbFileHandler;
    private ArchiveHandlerInterface|MockObject $dbArchiveHandler;
    private ArchiveHandlerInterface|MockObject $appArchiveHandler;
    /** @var list<string> */
    private array $writtenSql = [];

    /**
     * @throws ServiceException
     * @throws Exception
     */
    #[Test]
    public function testDoBackup(): void
    {
        $this->configureSuccessfulBackup();

        $this->fileBackupService->doBackup(TMP_PATH, APP_PATH);
    }

    /**
     * The dump must contain the schema DDL for every table and view, not only
     * the INSERT statements.
     *
     * @throws ServiceException
     * @throws Exception
     */
    #[Test]
    public function testDoBackupWritesCompleteSchemaDdl(): void
    {
        $this->configureSuccessfulBackup();

        $this->fileBackupService->doBackup(TMP_PATH, APP_PATH);

        $sql = implode('', $this->writtenSql);

        self::assertSame(25, substr_count($sql, 'CREATE TABLE `'));
        self::assertSame(2, substr_count($sql, 'CREATE VIEW `'));
        self::assertStringContainsString('CREATE TABLE `Client`', $sql);
        self::assertStringContainsString('CREATE VIEW `account_data_v`', $sql);
        self::assertStringContainsString('INSERT INTO `Client`', $sql);
    }

    /**
     * A symlink that matches a cleanup pattern must not make the cleanup follow
     * it outside the canonical backup root.
     *
     * @throws ServiceException
     * @throws Exception
     */
    #[Test]
    public function testDoBackupDoesNotFollowSymlinkWhenDeletingOldBackups(): void
    {
        $backupPath = TMP_PATH . DIRECTORY_SEPARATOR . 'backup-' . uniqid('', true);
        $outsideFile = TMP_PATH . DIRECTORY_SEPARATOR . 'outside-' . uniqid('', true) . '.sql';
        $link = $backupPath . DIRECTORY_SEPARATOR . 'sysPass_db-stale.sql';

        mkdir($backupPath);
        file_put_contents($outsideFile, 'keep');

        try {
            symlink($outsideFile, $link);

            $this->configureSuccessfulBackup();

            $this->fileBackupService->doBackup($backupPath, APP_PATH);

            $this->assertFileExists($outsideFile);
            $this->assertSame('keep', file_get_contents($outsideFile));
            $this->assertTrue(is_link($link));
        } finally {
            @unlink($link);
            @unlink($outsideFile);
            @rmdir($backupPath);
        }
    }

    #[Test]
    public function testDoBackupKeepsCurrentArtifactAndDeletesOlderOnes(): void
    {
        $backupPath = TMP_PATH . DIRECTORY_SEPARATOR . 'backup-' . uniqid('', true);

        mkdir($backupPath);

        $currentFiles = [
            $backupPath . DIRECTORY_SEPARATOR . basename((string)$this->backupFiles->getAppBackupFile()),
            $backupPath . DIRECTORY_SEPARATOR . basename((string)$this->backupFiles->getDbBackupFile()),
            $backupPath . DIRECTORY_SEPARATOR . basename((string)$this->backupFiles->getAppBackupFileCompressed()),
            $backupPath . DIRECTORY_SEPARATOR . basename((string)$this->backupFiles->getDbBackupFileCompressed()),
        ];

        $staleFiles = [
            $backupPath . DIRECTORY_SEPARATOR . 'sysPass_app-oldhash.tar.gz',
            $backupPath . DIRECTORY_SEPARATOR . 'sysPass_db-oldhash.tar.gz',
            $backupPath . DIRECTORY_SEPARATOR . 'sysPass-oldhash.sql',
        ];

        foreach (array_merge($currentFiles, $staleFiles) as $file) {
            file_put_contents($file, 'backup');
        }

        try {
            $this->configureSuccessfulBackup();

            $this->fileBackupService->doBackup($backupPath, APP_PATH);

            foreach ($currentFiles as $file) {
                $this->assertFileExists($file);
            }

            foreach ($staleFiles as $file) {
                $this->assertFileDoesNotExist($file);
            }
        } finally {
            foreach (array_merge($currentFiles, $staleFiles) as $file) {
                @unlink($file);
            }

            @rmdir($backupPath);
        }
    }

    private function configureSuccessfulBackup(): void
    {
        $this->config->getConfigData()->setDbName('a_db');

        $tablesCount = count(DatabaseTables::TABLES);

        $this->database
            ->expects(self::exactly($tablesCount * 2 - 2))
            ->method('doFetchWithOptions')
            ->willReturnCallback(function (QueryDataInterface $queryData, array $options, int $mode): Generator {
                $statement = $queryData->getQuery()->getStatement();

                if (preg_match('/^SHOW CREATE TABLE `([^`]+)`$/', $statement, $matches) === 1) {
                    $name = $matches[1];

                    if (str_ends_with($name, '_v')) {
                        return (static function () use ($name): Generator {
                            yield [
                                'View' => $name,
                                'Create View' => sprintf('CREATE VIEW `%s` AS SELECT 1', $name)
                            ];
                        })();
                    }

                    return (static function () use ($name): Generator {
                        yield [
                            'Table' => $name,
                            'Create Table' => sprintf('CREATE TABLE `%s` (`id` int)', $name)
                        ];
                    })();
                }

                self::assertSame([PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL], $options);
                self::assertSame(PDO::FETCH_NUM, $mode);

                return (static function (): Generator {
                    yield ['a', 1, false];
                    yield ['b', 2, true];
                })();
            });

        $this->dbFileHandler
            ->expects($this->exactly(79))
            ->method('write')
            ->willReturnCallback(function (string $data): FileHandlerInterface {
                $this->writtenSql[] = $data;

                return $this->dbFileHandler;
            });

        $this->dbFileHandler
            ->expects($this->once())
            ->method('delete');

        $file = self::$faker->colorName();

        $this->dbFileHandler
            ->expects($this->once())
            ->method('getFile')
            ->willReturn($file);

        $this->dbArchiveHandler
            ->expects($this->once())
            ->method('compressFile')
            ->with($file);

        $this->appArchiveHandler
            ->expects($this->once())
            ->method('compressDirectory')
            ->with(APP_PATH, BackupFile::BACKUP_INCLUDE_REGEX);
    }

    #[Test]
    public function testDoBackupFailsWhenTheSchemaDefinitionIsMissing(): void
    {
        $this->config->getConfigData()->setDbName('a_db');

        $this->dbFileHandler
            ->expects(self::once())
            ->method('write')
            ->willReturnSelf();

        $this->database
            ->expects(self::once())
            ->method('doFetchWithOptions')
            ->willReturn((static function (): Generator {
                yield from [];
            })());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while doing the backup');

        $this->fileBackupService->doBackup(TMP_PATH, APP_PATH);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     */
    #[Test]
    public function testDoBackupWithException(): void
    {
        $this->config->getConfigData()->setDbName('a_db');

        $this->dbFileHandler
            ->expects(self::any())
            ->method('write')
            ->willThrowException(FileException::error('Filexception'));

        $exception = new ServiceException(
            'Error while doing the backup',
            SPException::ERROR,
            'Please check out the event log for more details',
            0,
            new FileException('Filexception')
        );

        $this->expectException(ServiceException::class);
        $this->expectExceptionObject($exception);

        $this->fileBackupService->doBackup($this->pathsContext[Path::TMP], $this->pathsContext[Path::APP]);
    }

    /**
     * @throws Exception
     * @throws ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->writtenSql = [];
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->backupFiles = new BackupFiles(
            new BackupFileDto(BackupType::app, 'test', TMP_PATH, 'tar'),
            new BackupFileDto(BackupType::db, 'test', TMP_PATH, 'sql')
        );
        $this->dbFileHandler = $this->createMock(FileHandlerInterface::class);
        $this->dbArchiveHandler = $this->createMock(ArchiveHandlerInterface::class);
        $this->appArchiveHandler = $this->createMock(ArchiveHandlerInterface::class);

        $this->fileBackupService = new BackupFile(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->database,
            $this->createStub(DatabaseUtilInterface::class),
            $this->dbFileHandler,
            $this->dbArchiveHandler,
            $this->appArchiveHandler,
            $this->backupFiles,
            new \SP\Infrastructure\Database\QueryFactoryAdapter()
        );
    }
}
