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

namespace SP\Domain\Export\Ports;

use SP\Domain\Core\Exceptions\CheckException;
use SP\Infrastructure\File\ArchiveHandlerInterface;
use SP\Infrastructure\File\FileHandlerInterface;

/**
 * BackupFiles
 */
interface BackupFileHelperService
{
    /**
     * @return FileHandlerInterface
     */
    public function getAppBackupFileHandler(): FileHandlerInterface;

    /**
     * @return FileHandlerInterface
     */
    public function getDbBackupFileHandler(): FileHandlerInterface;

    /**
     * @return ArchiveHandlerInterface
     * @throws CheckException
     */
    public function getDbBackupArchiveHandler(): ArchiveHandlerInterface;

    /**
     * @return ArchiveHandlerInterface
     * @throws CheckException
     */
    public function getAppBackupArchiveHandler(): ArchiveHandlerInterface;

    /**
     * @return string
     */
    public function getHash(): string;
}
