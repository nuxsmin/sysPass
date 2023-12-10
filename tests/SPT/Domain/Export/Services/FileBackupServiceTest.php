<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Domain\Export\Services;

use PDO;
use PDOStatement;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Export\Ports\BackupFilesInterface;
use SP\Domain\Export\Services\FileBackupService;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\DbStorageInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\ArchiveHandlerInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;
use SPT\UnitaryTestCase;

/**
 * Class FileBackupServiceTest
 *
 * @group unitary
 */
class FileBackupServiceTest extends UnitaryTestCase
{
    private FileBackupService               $fileBackupService;
    private BackupFilesInterface|MockObject $backupFiles;
    private DatabaseInterface|MockObject    $database;

    /**
     * @throws ServiceException
     * @throws Exception
     */
    public function testDoBackup(): void
    {
        $this->setupBackupFiles();

        $dbStorage = $this->createStub(DbStorageInterface::class);
        $dbStorage->method('getDatabaseName')
                  ->willReturn('TestDatabase');

        $this->database
            ->method('getDbHandler')
            ->willReturn($dbStorage);

        $tablesCount = count(DatabaseUtil::TABLES);
        $tablesType = ['table', 'view'];

        $queryResults = array_map(
            fn($i) => $this->buildCreateResult($tablesType[$i % 2]),
            range(0, $tablesCount)
        );

        $this->database
            ->expects(self::exactly($tablesCount))
            ->method('doQuery')
            ->with(
                new Callback(static function (QueryData $queryData) {
                    return preg_match('/^SHOW CREATE TABLE \w+$/', $queryData->getQuery()) === 1;
                })
            )
            ->willReturn(...$queryResults);

        $statement = $this->createMock(PDOStatement::class);
        $statement->expects(self::exactly($tablesCount - 1))
                  ->method('fetch')
                  ->with(PDO::FETCH_NUM)
                  ->willReturn(['value1', 2, null], false);

        $this->database
            ->expects(self::exactly($tablesCount - 2))
            ->method('doQueryRaw')
            ->with(
                new Callback(static function (QueryData $queryData) {
                    return preg_match('/^SELECT \* FROM `\w+`$/', $queryData->getQuery()) === 1;
                }),
                [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL],
                false
            )
            ->willReturn($statement);

        $this->fileBackupService->doBackup(TMP_PATH);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function setupBackupFiles(): void
    {
        $archiveHandler = $this->createMock(ArchiveHandlerInterface::class);
        $archiveHandler->expects(self::once())
                       ->method('compressFile')
                       ->withAnyParameters();
        $archiveHandler->expects(self::once())
                       ->method('compressDirectory')
                       ->with(
                           APP_ROOT,
                           FileBackupService::BACKUP_INCLUDE_REGEX
                       );

        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(self::once())
                    ->method('open')
                    ->with('w');
        $fileHandler->expects(self::atLeast(5))
                    ->method('write')
                    ->with(self::anything());
        $fileHandler->expects(self::once())
                    ->method('close');
        $fileHandler->expects(self::once())
                    ->method('getFile');
        $fileHandler->expects(self::once())
                    ->method('delete');

        $this->backupFiles
            ->expects(self::once())
            ->method('getDbBackupFileHandler')
            ->willReturn($fileHandler);
        $this->backupFiles
            ->expects(self::once())
            ->method('getDbBackupArchiveHandler')
            ->willReturn($archiveHandler);
        $this->backupFiles
            ->expects(self::once())
            ->method('getAppBackupArchiveHandler')
            ->willReturn($archiveHandler);
    }

    private function buildCreateResult(string $type): QueryResult
    {
        $data = new Simple();

        switch ($type) {
            case 'table':
                $data->{'Create Table'} = 'CREATE TABLE';
                break;
            case 'view':
                $data->{'Create View'} = 'CREATE VIEW';
                break;
        }

        return new QueryResult([$data]);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     */
    public function testDoBackupWithException(): void
    {
        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(self::once())
                    ->method('open')
                    ->with('w')
                    ->willThrowException(new FileException('Filexception'));

        $this->backupFiles
            ->expects(self::once())
            ->method('getDbBackupFileHandler')
            ->willReturn($fileHandler);

        $exception = new ServiceException(
            'Error while doing the backup',
            SPException::ERROR,
            'Please check out the event log for more details',
            0,
            new FileException('Filexception')
        );

        $this->expectException(ServiceException::class);
        $this->expectExceptionObject($exception);

        $this->fileBackupService->doBackup();
    }

    public function testGetHash(): void
    {
        $hash = self::$faker->sha1;

        $this->backupFiles
            ->expects(self::once())
            ->method('getHash')
            ->willReturn($hash);

        self::assertEquals($hash, $this->fileBackupService->getHash());
    }

    /**
     * @throws Exception
     * @throws ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $this->backupFiles = $this->createMock(BackupFilesInterface::class);

        $this->fileBackupService = new FileBackupService(
            $this->application,
            $this->database,
            $this->createStub(DatabaseUtil::class),
            $this->backupFiles
        );
    }
}