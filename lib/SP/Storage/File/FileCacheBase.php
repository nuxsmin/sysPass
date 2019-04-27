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

namespace SP\Storage\File;


/**
 * Class FileCacheBase
 *
 * @package SP\Storage\File
 */
abstract class FileCacheBase implements FileCacheInterface
{
    /**
     * @var FileHandler
     */
    protected $path;

    /**
     * FileCacheBase constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = new FileHandler($path);
    }

    /**
     * @param $path
     *
     * @return FileCacheBase
     */
    public static function factory($path)
    {
        return new static($path);
    }

    /**
     * Returns if the file is expired adding time to modification date
     *
     * @param int $time
     *
     * @return bool
     * @throws FileException
     */
    public function isExpired($time = 86400): bool
    {
        $this->path->checkFileExists();

        return time() > $this->path->getFileTime() + $time;
    }

    /**
     * Returns if the file is expired adding time to modification date
     *
     * @param int $date
     *
     * @return bool
     * @throws FileException
     */
    public function isExpiredDate($date): bool
    {
        $this->path->checkFileExists();

        return (int)$date > $this->path->getFileTime();
    }

    /**
     * @throws FileException
     */
    public function createPath()
    {
        $path = dirname($this->path->getFile());

        if (!is_dir($path) && mkdir($path, 0700, true) === false) {
            throw new FileException(sprintf(__('Unable to create the directory (%s)'), $path));
        }
    }

    /**
     * @return FileCacheInterface
     * @throws FileException
     */
    public function delete()
    {
        $this->path->delete();

        return $this;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->path->getFile());
    }
}