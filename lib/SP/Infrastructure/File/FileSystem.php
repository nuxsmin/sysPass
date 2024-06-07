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

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SP\Domain\Account\Models\File;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\Exceptions\InvalidClassException;

use function SP\__;
use function SP\__u;

/**
 * Class FileSystem
 */
class FileSystem
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
     * @template T
     * @param string $file
     * @param class-string<T>|null $class
     *
     * @return mixed|T
     * @throws FileException
     * @throws InvalidClassException
     */
    public static function require(string $file, ?string $class = null): mixed
    {
        if (file_exists($file)) {
            $out = require $file;

            if ($class && class_exists($class) && !$out instanceof $class) {
                throw InvalidClassException::error(__u('Invalid class for loaded file data'));
            }

            return $out;
        }

        throw FileException::error(sprintf(__('File not found: %s'), $file));
    }

    /**
     * Comprueba y devuelve un directorio temporal válido
     *
     * @return false|string
     */
    public static function getTempDir(): false|string
    {
        $sysTmp = sys_get_temp_dir();

        $checkDir = static function ($dir) {
            $path = self::buildPath($dir, 'syspass.test');

            if (file_exists($path)) {
                return $dir;
            }

            if (is_dir($dir) || mkdir($dir) || is_dir($dir)) {
                if (touch($path)) {
                    return $dir;
                }
            }

            return false;
        };

        if ($checkDir(TMP_PATH)) {
            return TMP_PATH;
        }

        return $checkDir($sysTmp);
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
     * Delete files using {@link glob()} patterns
     *
     * @param string $path
     * @param string ...$patterns
     * @return void
     */
    public static function deleteByPattern(string $path, string...$patterns): void
    {
        array_map(
            static fn(string $file) => @unlink($file),
            array_merge(...array_map(static fn(string $pattern) => glob(self::buildPath($path, $pattern)), $patterns))
        );
    }
}
