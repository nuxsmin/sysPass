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

use RuntimeException;

/**
 * Class FileCachePacked
 *
 * @package SP\Storage\File;
 */
final class FileCachePacked implements FileStorageInterface
{
    /**
     * @var array
     */
    protected $data;
    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @param string $path
     *
     * @return mixed
     * @throws \RuntimeException
     * @throws FileException
     */
    public function load($path)
    {
        $file = new FileHandler($path);

        if (!($data = gzuncompress($file->checkIsReadable()->readToString()))) {
            throw new FileException(sprintf(__('Error while decompressing the file data (%s)'), $path));
        }

        if (($this->data = unserialize($data)) === false) {
            throw new FileException(__('Error while retrieving the data'));
        }

        $this->loaded = true;

        return $this;
    }

    /**
     * @param string $path
     * @param mixed  $data
     *
     * @return FileStorageInterface
     * @throws FileException
     */
    public function save($path, $data = null)
    {
        if ($data === null) {
            $this->saveData($path, $this->data);
        } else {
            $this->saveData($path, $data);
        }

        return $this;
    }

    /**
     * @param $path
     * @param $data
     *
     * @throws FileException
     */
    protected function saveData($path, $data)
    {

        $this->createPath(dirname($path));

        if (!($data = gzcompress(serialize($data)))) {
            throw new FileException(sprintf(__('Error while compressing the file data (%s)'), $path));
        }

        $file = new FileHandler($path);
        $file->checkIsWritable()
            ->write(gzcompress(serialize($data)))
            ->close();
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
     * Gets key data from cache
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->loaded) {
            throw new RuntimeException((__('Data not loaded')));
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Sets key data into cache
     *
     * @param $key
     * @param $data
     */
    public function set($key, $data)
    {
        if (!$this->loaded) {
            $this->data = [];
        }

        $this->data[$key] = ['time' => time(), 'data' => serialize($data)];
    }

    /**
     * Returns whether the file is expired
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