<?php
/**
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

declare(strict_types=1);

namespace SP\Domain\Export\Dtos;

/**
 * Class BackupFiles
 */
final readonly class BackupFiles
{
    private const        BACKUP_PREFIX = 'sysPassBackup';

    public function __construct(private BackupFile $appBackupFile, private BackupFile $dbBackupFile)
    {
    }

    public static function buildHash(): string
    {
        return sha1(uniqid(self::BACKUP_PREFIX, true));
    }

    public function getAppBackupFile(): BackupFile
    {
        return $this->appBackupFile;
    }

    public function getDbBackupFile(): BackupFile
    {
        return $this->dbBackupFile;
    }

    public function withPath(string $path): BackupFiles
    {
        return new self($this->appBackupFile->withPath($path), $this->dbBackupFile->withPath($path));
    }

    public function withHash(string $hash): BackupFiles
    {
        return new self($this->appBackupFile->withHash($hash), $this->dbBackupFile->withHash($hash));
    }
}
