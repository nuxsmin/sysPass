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

use RuntimeException;

/**
 * Class FileCachePacked
 *
 * @package SP\Storage\File;
 */
final class FileCachePacked extends FileCacheBase
{
    /**
     * @return mixed
     * @throws RuntimeException
     * @throws FileException
     */
    public function load()
    {
        $this->path->checkIsReadable();
        $dataUnpacked = gzuncompress($this->path->readToString());

        if ($dataUnpacked === false) {
            throw new FileException(sprintf(__('Error while decompressing the file data (%s)'), $this->path->getFile()));
        }

        $data = unserialize($dataUnpacked);

        if ($data === false) {
            throw new FileException(__('Error while retrieving the data'));
        }

        return $data;
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

        $data = gzcompress(serialize($data));

        if ($data === false) {
            throw new FileException(sprintf(__('Error while compressing the file data (%s)'), $this->path->getFile()));
        }

        $this->path->checkIsWritable()
            ->write($data)
            ->close();

        return $this;
    }
}