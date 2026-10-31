<?php

declare(strict_types=1);
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

namespace SP\Infrastructure\File;

use SP\Domain\File\Ports\FileSystemService;

/**
 * Infrastructure adapter for the filesystem helper port.
 */
final class FileSystemAdapter implements FileSystemService
{
    public function buildPath(string ...$parts): string
    {
        return FileSystem::buildPath(...$parts);
    }

    public function isImage(string $fileType): bool
    {
        return FileSystem::isImage($fileType);
    }

    public function removeDirectory(string $dir): bool
    {
        return FileSystem::rmdirRecursive($dir);
    }

    public function requireFile(string $file, ?string $class = null): mixed
    {
        return FileSystem::require($file, $class);
    }

    public function deleteByPattern(string $path, string ...$patterns): void
    {
        FileSystem::deleteByPattern($path, ...$patterns);
    }
}
