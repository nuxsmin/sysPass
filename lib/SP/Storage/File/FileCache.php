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
 * Class FileCache
 *
 * @package SP\Storage\File;
 */
final class FileCache extends FileCacheBase
{
    /**
     * @return mixed
     * @throws FileException
     */
    public function load()
    {
        return unserialize($this->path->checkIsReadable()->readToString());
    }

    /**
     * @param mixed $data
     *
     * @return FileCacheInterface
     * @throws FileException
     */
    public function save($data)
    {
        $this->createPath();

        $this->path->checkIsWritable();
        $this->path->open('wb', true);
        $this->path->write(serialize($data));
        $this->path->close();

        return $this;
    }
}