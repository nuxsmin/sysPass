<?php
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

namespace SPT\Domain\Export\Services;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Export\Services\BackupFileHelper;
use SP\Domain\File\Ports\DirectoryHandlerService;
use SPT\Stubs\PhpExtensionCheckerStub;
use SPT\UnitaryTestCase;

/**
 * Class BackupFileHelperTest
 *
 * @group unitary
 */
class BackupFileHelperTest extends UnitaryTestCase
{

    private PhpExtensionCheckerService|MockObject $phpExtensionCheckerService;
    private BackupFileHelper                      $backupFileHelper;

    public function testGetAppBackupFilename()
    {
        $hash = self::$faker->sha1();
        $out = BackupFileHelper::getAppBackupFilename('/a/path', $hash);
        $expected = sprintf('/a/path/sysPass_app-%s', $hash);

        $this->assertEquals($expected, $out);
    }

    public function testGetAppBackupFilenameWithCompress()
    {
        $hash = self::$faker->sha1();
        $out = BackupFileHelper::getAppBackupFilename('/a/path', $hash, true);
        $expected = sprintf('/a/path/sysPass_app-%s.tar.gz', $hash);

        $this->assertEquals($expected, $out);
    }

    public function testGetDbBackupFilename()
    {
        $hash = self::$faker->sha1();
        $out = BackupFileHelper::getDbBackupFilename('/a/path', $hash);
        $expected = sprintf('/a/path/sysPass_db-%s.sql', $hash);

        $this->assertEquals($expected, $out);
    }

    public function testGetDbBackupFilenameWithCompress()
    {
        $hash = self::$faker->sha1();
        $out = BackupFileHelper::getDbBackupFilename('/a/path', $hash, true);
        $expected = sprintf('/a/path/sysPass_db-%s.tar.gz', $hash);

        $this->assertEquals($expected, $out);
    }

    public function testGetDbBackupArchiveHandler()
    {
        $this->phpExtensionCheckerService
            ->expects(self::once())
            ->method('checkPhar')
            ->with(true);

        $this->backupFileHelper->getDbBackupArchiveHandler();
    }

    public function testGetAppBackupFileHandler()
    {
        $this->phpExtensionCheckerService
            ->expects(self::once())
            ->method('checkPhar')
            ->with(true);

        $this->backupFileHelper->getAppBackupArchiveHandler();
    }

    public function testGetDbBackupFileHandler()
    {
        $expected = BackupFileHelper::getDbBackupFilename(TMP_PATH, $this->backupFileHelper->getHash());
        $out = $this->backupFileHelper->getDbBackupFileHandler();

        $this->assertEquals($expected, $out->getFile());
    }

    public function testGetAppBackupArchiveHandler()
    {
        $expected = BackupFileHelper::getAppBackupFilename(TMP_PATH, $this->backupFileHelper->getHash());
        $out = $this->backupFileHelper->getAppBackupFileHandler();

        $this->assertEquals($expected, $out->getFile());
    }

    /**
     * @throws Exception
     * @throws ContextException
     * @throws CheckException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->phpExtensionCheckerService = $this->createMock(PhpExtensionCheckerStub::class);

        $directoryHandlerService = $this->createMock(DirectoryHandlerService::class);
        $directoryHandlerService
            ->expects(self::once())
            ->method('checkOrCreate');

        $directoryHandlerService
            ->expects(self::exactly(2))
            ->method('getPath')
            ->willReturn(TMP_PATH);

        $this->backupFileHelper = new BackupFileHelper(
            $this->phpExtensionCheckerService,
            $directoryHandlerService
        );
    }
}
