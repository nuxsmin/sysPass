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

namespace SP\Domain\Export\Services;

use SP\Core\Exceptions\CheckException;
use SP\Core\PhpExtensionChecker;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Export\Ports\BackupFilesInterface;
use SP\Infrastructure\File\ArchiveHandler;
use SP\Infrastructure\File\ArchiveHandlerInterface;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileHandlerInterface;

use function SP\__;
use function SP\__u;

/**
 * BackupFiles
 */
final class BackupFiles implements BackupFilesInterface
{
    private const BACKUP_PREFFIX = 'sysPassBackup';
    private string $hash;
    private string $path;
    private string $appBackupFilename;
    private string $dbBackupFilename;
    /**
     * @var PhpExtensionChecker
     */
    private PhpExtensionChecker $extensionChecker;

    /**
     * @param string $path The path where to store the backup files
     *
     * @throws CheckException
     */
    public function __construct(PhpExtensionChecker $extensionChecker, string $path = BACKUP_PATH)
    {
        $this->extensionChecker = $extensionChecker;
        $this->path = $path;
        $this->hash = $this->getBackupHash();

        $this->checkBackupDir();

        $this->appBackupFilename = self::getAppBackupFilename($this->path, $this->hash);
        $this->dbBackupFilename = self::getDbBackupFilename($this->path, $this->hash);
    }

    /**
     * Generate a unique hash to avoid unwated downloads
     *
     * @return string
     */
    private function getBackupHash(): string
    {
        return sha1(uniqid(self::BACKUP_PREFFIX, true));
    }

    /**
     * Check and create the backup dir
     *
     * @throws CheckException
     */
    private function checkBackupDir(): void
    {
        if (is_dir($this->path) === false
            && !@mkdir($concurrentDirectory = $this->path, 0750, true)
            && !is_dir($concurrentDirectory)
        ) {
            throw new CheckException(
                sprintf(__('Unable to create the backups directory ("%s")'), $this->path)
            );
        }

        if (!is_writable($this->path)) {
            throw new CheckException(
                __u('Please, check the backup directory permissions')
            );
        }
    }

    public static function getAppBackupFilename(
        string $path,
        string $hash,
        bool   $compressed = false
    ): string {
        $file = $path . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME . '_app-' . $hash;

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
        $file = $path . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME . '_db-' . $hash;

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return $file . '.sql';
    }

    /**
     * @return FileHandlerInterface
     */
    public function getAppBackupFileHandler(): FileHandlerInterface
    {
        return new FileHandler($this->appBackupFilename);
    }

    /**
     * @return FileHandlerInterface
     */
    public function getDbBackupFileHandler(): FileHandlerInterface
    {
        return new FileHandler($this->dbBackupFilename);
    }

    /**
     * @return ArchiveHandlerInterface
     * @throws CheckException
     */
    public function getDbBackupArchiveHandler(): ArchiveHandlerInterface
    {
        return new ArchiveHandler($this->dbBackupFilename, $this->extensionChecker);
    }

    /**
     * @return ArchiveHandlerInterface
     * @throws CheckException
     */
    public function getAppBackupArchiveHandler(): ArchiveHandlerInterface
    {
        return new ArchiveHandler($this->appBackupFilename, $this->extensionChecker);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }
}
