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

namespace SP\Infrastructure\File;

use Directory;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\File\Ports\DirectoryHandlerService;

use function SP\__u;

/**
 * Class DirectoryHandler
 */
final readonly class DirectoryHandler implements DirectoryHandlerService
{
    public function __construct(private string $path)
    {
    }

    /**
     * @throws CheckException
     */
    public function checkOrCreate(): void
    {
        if (!$this->isDir() && !$this->create()) {
            throw CheckException::error(sprintf(__u('Unable to create directory ("%s")'), $this->path));
        }

        if (!$this->isWritable()) {
            throw CheckException::error(__u('Please, check the directory permissions'));
        }
    }

    public function isDir(): bool
    {
        return @is_dir($this->path);
    }

    public function create(int $permissions = 0750): bool
    {
        return @mkdir($this->path, $permissions, true);
    }

    public function isWritable(): bool
    {
        return @is_writable($this->path);
    }

    /**
     * @throws CheckException
     */
    public function getDir(): Directory
    {
        $dir = dir($this->path);

        if (!$dir) {
            throw CheckException::error(sprintf(__u('Unable to open directory ("%s")'), $this->path));
        }

        return $dir;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
