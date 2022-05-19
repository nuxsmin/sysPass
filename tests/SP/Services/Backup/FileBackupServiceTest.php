<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Services\Backup;

use SP\Core\PhpExtensionChecker;
use SP\Services\Backup\BackupFiles;
use SP\Services\Backup\FileBackupService;
use SP\Storage\Database\Database;
use SP\Storage\Database\DatabaseUtil;
use SP\Storage\Database\MySQLHandler;
use SP\Storage\File\ArchiveHandler;
use SP\Tests\UnitaryTestCase;

/**
 * Class FileBackupServiceTest
 *
 * @package SP\Tests\Services\Backup
 */
class FileBackupServiceTest extends UnitaryTestCase
{
    private FileBackupService $fileBackupService;
    private BackupFiles       $backupFiles;

    /**
     * @throws \SP\Services\ServiceException
     */
    public function testDoBackup(): void
    {
        $this->fileBackupService->doBackup(TMP_PATH, APP_ROOT);
    }

    /**
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Context\ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $database = $this->createStub(Database::class);
        $database->method('getDbHandler')->willReturn(
            $this->createStub(MySQLHandler::class)
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

        $this->backupFiles = $this->getMockBuilder(BackupFiles::class)
            ->onlyMethods(['getDbBackupArchiveHandler', 'getAppBackupArchiveHandler'])
            ->setConstructorArgs([new PhpExtensionChecker()])
            ->getMock();
        $this->backupFiles->method('getDbBackupArchiveHandler')->willReturn($archiveHandler);
        $this->backupFiles->method('getAppBackupArchiveHandler')->willReturn($archiveHandler);

        $this->fileBackupService = new FileBackupService(
            $this->application,
            $database,
            $this->createStub(DatabaseUtil::class),
            $this->backupFiles
        );
    }
}
