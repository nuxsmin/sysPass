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

namespace SP\Util;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SP\Domain\Account\Models\File;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\File\FileException;

use function SP\__;
use function SP\__u;

/**
 * Class FileUtil
 *
 * @package SP\Util
 */
class FileUtil
{
    private const IMAGE_MIME = [
        'image/jpeg',
        'image/png',
        'image/bmp',
        'image/gif',
    ];

    /**
     * Removes a directory in a recursive way
     *
     * @throws FileNotFoundException
     * @see https://stackoverflow.com/a/7288067
     */
    public static function rmdirRecursive(string $dir): bool
    {
        if (!is_dir($dir)) {
            throw new FileNotFoundException('Directory does not exist');
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        return rmdir($dir);
    }

    public static function isImage(File $fileData): bool
    {
        return in_array(strtolower($fileData->getType()), self::IMAGE_MIME, true);
    }

    /**
     * Return a well-formed path
     *
     * @param string ...$parts
     * @return string
     */
    public static function buildPath(string ...$parts): string
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * @template T
     * @param string $file
     * @param class-string<T>|null $class
     *
     * @return null|T
     * @throws FileException
     * @throws InvalidClassException
     */
    public static function require(string $file, ?string $class = null): mixed
    {
        if (file_exists($file)) {
            $out = require $file;

            if ($class && class_exists($class) && !$out instanceof $class) {
                throw new InvalidClassException(__u('Invalid class for loaded file data'));
            }

            return $out;
        } else {
            throw new FileException(sprintf(__('File not found: %s'), $file));
        }
    }
}
