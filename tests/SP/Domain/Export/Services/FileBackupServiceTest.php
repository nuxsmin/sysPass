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
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Bootstrap\Path;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Export\Ports\BackupFileHelperService;
use SP\Domain\Export\Services\BackupFile;
use SP\Domain\File\Ports\ArchiveHandlerInterface;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;
use stdClass;

/**
 * Class FileBackupServiceTest
 *
 */
#[Group('unitary')]
class FileBackupServiceTest extends UnitaryTestCase
{
    private BackupFile                         $fileBackupService;
    private BackupFileHelperService|MockObject $backupFiles;
    private DatabaseInterface|MockObject       $database;
    private MockObject|FileHandlerInterface    $dbFileHandler;
    private ArchiveHandlerInterface|MockObject $dbArchiveHandler;
    private ArchiveHandlerInterface|MockObject $appArchiveHandler;

    /**
     * @throws ServiceException
     * @throws Exception
     */
    public function testDoBackup(): void
    {
        $this->config->getConfigData()->setDbName('a_db');

        $tablesCount = count(DatabaseUtil::TABLES);
        $tablesType = ['table', 'view'];

        $queryResults = array_map(
            fn($i) => $this->buildCreateResult($tablesType[$i % 2]),
            range(0, $tablesCount)
        );

        $this->database
            ->expects(self::exactly($tablesCount))
            ->method('runQuery')
            ->with(
                new Callback(static function (QueryData $queryData) {
                    return preg_match('/^SHOW CREATE TABLE \w+$/', $queryData->getQuery()->getStatement()) === 1;
                })
            )
            ->willReturn(...$queryResults);

        $rows = static function () {
            yield ['a', 1, false];
            yield ['b', 2, true];
        };

        $this->database
            ->expects(self::exactly($tablesCount - 2))
            ->method('doFetchWithOptions')
            ->with(
                new Callback(static function (QueryData $queryData) {
                    return preg_match('/^SELECT \* FROM `\w+`$/', $queryData->getQuery()->getStatement()) === 1;
                }),
                [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL],
                PDO::FETCH_NUM,
                false
            )
            ->willReturnCallback($rows);

        $this->dbFileHandler
            ->expects($this->exactly(79))
            ->method('write');

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

        $this->fileBackupService->doBackup(TMP_PATH, APP_PATH);
    }

    private function buildCreateResult(string $type): QueryResult
    {
        $data = new stdClass();

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

        $this->database = $this->createMock(DatabaseInterface::class);
        $this->backupFiles = $this->createMock(BackupFileHelperService::class);
        $this->dbFileHandler = $this->createMock(FileHandlerInterface::class);
        $this->dbArchiveHandler = $this->createMock(ArchiveHandlerInterface::class);
        $this->appArchiveHandler = $this->createMock(ArchiveHandlerInterface::class);

        $this->fileBackupService = new BackupFile(
            $this->application,
            $this->database,
            $this->createStub(DatabaseUtil::class),
            $this->dbFileHandler,
            $this->dbArchiveHandler,
            $this->appArchiveHandler
        );
    }
}
