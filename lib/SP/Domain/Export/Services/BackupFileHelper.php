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

namespace SP\Domain\Export\Services;

use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Export\Ports\BackupFileHelperService;
use SP\Domain\File\Ports\DirectoryHandlerService;
use SP\Infrastructure\File\ArchiveHandler;
use SP\Infrastructure\File\ArchiveHandlerInterface;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileHandlerInterface;
use SP\Util\FileUtil;

/**
 * BackupFileHelper
 */
final class BackupFileHelper implements BackupFileHelperService
{
    private const BACKUP_PREFFIX = 'sysPassBackup';
    private string $hash;
    private string $appBackupFilename;
    private string $dbBackupFilename;

    /**
     * @throws CheckException
     */
    public function __construct(
        private readonly PhpExtensionCheckerService $phpExtensionCheckerService,
        private readonly DirectoryHandlerService    $directoryHandlerService
    ) {
        $this->hash = BackupFileHelper::buildHash();
        $this->directoryHandlerService->checkOrCreate();
        $this->appBackupFilename = self::getAppBackupFilename($this->directoryHandlerService->getPath(), $this->hash);
        $this->dbBackupFilename = self::getDbBackupFilename($this->directoryHandlerService->getPath(), $this->hash);
    }

    /**
     * Generate a unique hash to avoid unwanted downloads
     *
     * @return string
     */
    private static function buildHash(): string
    {
        return sha1(uniqid(self::BACKUP_PREFFIX, true));
    }

    public static function getAppBackupFilename(
        string $path,
        string $hash,
        bool   $compressed = false
    ): string {
        $file = sprintf('%s_app-%s', FileUtil::buildPath($path, AppInfoInterface::APP_NAME), $hash);

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return $file;
    }

    public static function getDbBackupFilename(
        string $path,
        string $hash,
        bool   $compressed = false
    ): string {
        $file = sprintf('%s_db-%s', FileUtil::buildPath($path, AppInfoInterface::APP_NAME), $hash);

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return $file . '.sql';
    }

    /**
     * @return FileHandlerInterface
     */
    public function getDbBackupFileHandler(): FileHandlerInterface
    {
        return new FileHandler($this->dbBackupFilename, 'w');
    }

    /**
     * @return ArchiveHandlerInterface
     */
    public function getDbBackupArchiveHandler(): ArchiveHandlerInterface
    {
        return new ArchiveHandler($this->dbBackupFilename, $this->phpExtensionCheckerService);
    }

    /**
     * @return ArchiveHandlerInterface
     */
    public function getAppBackupArchiveHandler(): ArchiveHandlerInterface
    {
        return new ArchiveHandler($this->appBackupFilename, $this->phpExtensionCheckerService);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }
}
