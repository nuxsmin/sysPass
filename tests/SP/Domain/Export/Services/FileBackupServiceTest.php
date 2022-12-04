<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\PhpExtensionChecker;
use SP\Domain\Export\Services\BackupFiles;
use SP\Domain\Export\Services\FileBackupService;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\File\ArchiveHandler;
use SP\Tests\UnitaryTestCase;

/**
 * Class FileBackupServiceTest
 *
 * @group unitary
 */
class FileBackupServiceTest extends UnitaryTestCase
{
    private FileBackupService $fileBackupService;

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     *
     * TODO: Database query must be reworked.
     */
    public function testDoBackup(): void
    {
        $this->markTestSkipped('Database query must be reworked.');

        $this->fileBackupService->doBackup(TMP_PATH, APP_ROOT);
    }

    /**
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Context\ContextException
     * @noinspection ClassMockingCorrectnessInspection
     * @noinspection PhpUnitInvalidMockingEntityInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $database = $this->createStub(Database::class);
        $database->method('getDbHandler')->willReturn(
            $this->createStub(MysqlHandler::class)
        );

        $archiveHandler = $this->createMock(ArchiveHandler::class);
        $archiveHandler->expects(self::once())
            ->method('compressFile')
            ->withAnyParameters();
        $archiveHandler->expects(self::once())
            ->method('compressDirectory')
            ->with(
                APP_ROOT,
                FileBackupService::BACKUP_INCLUDE_REGEX
            );

        $backupFiles = $this->getMockBuilder(BackupFiles::class)
            ->onlyMethods(['getDbBackupArchiveHandler', 'getAppBackupArchiveHandler'])
            ->setConstructorArgs([new PhpExtensionChecker()])
            ->getMock();
        $backupFiles->method('getDbBackupArchiveHandler')->willReturn($archiveHandler);
        $backupFiles->method('getAppBackupArchiveHandler')->willReturn($archiveHandler);

        $this->fileBackupService = new FileBackupService(
            $this->application,
            $database,
            $this->createStub(DatabaseUtil::class),
            $backupFiles
        );
    }
}
