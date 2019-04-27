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
 * Interface StorageInterface
 *
 * @package SP\Storage
 */
interface XmlFileStorageInterface
{
    /**
     * @param string $node
     *
     * @return XmlFileStorageInterface
     * @throws FileException
     */
    public function load($node = '');

    /**
     * @param mixed  $data Data to be saved
     * @param string $node
     *
     * @return XmlFileStorageInterface
     * @throws FileException
     */
    public function save($data, $node = '');

    /**
     * @return mixed
     */
    public function getItems();

    /**
     * Returns the given path node value
     *
     * @param $path
     *
     * @return string
     * @throws FileException
     */
    public function getPathValue($path);

    /**
     * @return FileHandler
     */
    public function getFileHandler(): FileHandler;
}