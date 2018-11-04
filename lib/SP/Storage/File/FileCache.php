<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Storage\File;

/**
 * Class FileCache
 *
 * @package SP\Storage\File;
 */
final class FileCache implements FileStorageInterface
{
    /**
     * @param string $path
     *
     * @return mixed
     * @throws FileException
     */
    public function load($path)
    {
        $file = new FileHandler($path);

        return unserialize($file->checkIsReadable()->readToString());
    }

    /**
     * @param string $path
     * @param mixed  $data
     *
     * @return FileStorageInterface
     * @throws FileException
     */
    public function save($path, $data)
    {
        $this->createPath(dirname($path));

        $file = new FileHandler($path);
        $file->checkIsWritable()
            ->write(serialize($data))
            ->close();

        return $this;
    }

    /**
     * @param $path
     *
     * @throws FileException
     */
    public function createPath($path)
    {
        if (!is_dir($path) && mkdir($path, 0700, true) === false) {
            throw new FileException(sprintf(__('Unable to create the directory (%s)'), $path));
        }
    }

    /**
     * @param string $path
     *
     * @return FileStorageInterface
     * @throws FileException
     */
    public function delete($path)
    {
        $file = new FileHandler($path);
        $file->delete();

        return $this;
    }

    /**
     * Returns if the file is expired adding time to modification date
     *
     * @param string $path
     * @param int    $time
     *
     * @return bool
     * @throws FileException
     */
    public function isExpired($path, $time = 86400): bool
    {
        $file = new FileHandler($path);
        $file->checkFileExists();

        return time() > $file->getFileTime() + $time;
    }

    /**
     * Returns if the file is expired adding time to modification date
     *
     * @param string $path
     * @param int    $date
     *
     * @return bool
     * @throws FileException
     */
    public function isExpiredDate($path, $date): bool
    {
        $file = new FileHandler($path);
        $file->checkFileExists();

        return (int)$date > $file->getFileTime();
    }
}