<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Util;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SP\Core\Exceptions\FileNotFoundException;
use SP\DataModel\FileData;

/**
 * Class FileUtil
 *
 * @package SP\Util
 */
final class FileUtil
{
    const IMAGE_MIME = ['image/jpeg', 'image/png', 'image/bmp', 'image/gif'];

    /**
     * Removes a directory in a recursive way
     *
     * @param $dir
     *
     * @return bool
     * @throws FileNotFoundException
     * @see https://stackoverflow.com/a/7288067
     */
    public static function rmdir_recursive($dir)
    {
        if (!is_dir($dir)) {
            throw new FileNotFoundException('Directory does not exist');
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($it as $file) {
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }

        return rmdir($dir);
    }

    /**
     * @param FileData $fileData
     *
     * @return bool
     */
    public static function isImage(FileData $fileData)
    {
        return in_array(strtolower($fileData->getType()), self::IMAGE_MIME, true);
    }
}