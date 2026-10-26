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

use SP\Domain\Common\Dtos\Dto;
use SP\Domain\Core\AppInfoInterface;
use SP\Infrastructure\File\FileSystem;

/**
 * Class BackupFile
 */
final class BackupFile extends Dto
{
    public function __construct(
        private readonly BackupType $backupType,
        private readonly string     $hash,
        private readonly string     $path,
        private readonly string     $extension
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s_%s-%s.%s',
            FileSystem::buildPath($this->path, AppInfoInterface::APP_NAME),
            $this->backupType->name,
            $this->hash,
            $this->extension
        );
    }

    public function withPath(string $path): BackupFile
    {
        return new self($this->backupType, $this->hash, $path, $this->extension);
    }

    public function withHash(string $hash): BackupFile
    {
        return new self($this->backupType, $hash, $this->path, $this->extension);
    }
}
