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
 * Interface FileStorageInterface
 *
 * @package SP\Storage\File;
 */
interface FileStorageInterface
{
    /**
     * @param string $path
     *
     * @return mixed
     * @throws FileException
     */
    public function load($path);

    /**
     * @param string $path
     * @param mixed  $data
     *
     * @return FileStorageInterface
     * @throws FileException
     */
    public function save($path, $data);

    /**
     * @param string $path
     *
     * @return mixed
     */
    public function delete($path);

    /**
     * Returns whether the file is expired
     *
     * @param string $path
     * @param int    $time
     *
     * @return bool
     */
    public function isExpired($path, $time = 86400): bool;

    /**
     * Returns if the file is expired adding time to modification date
     *
     * @param string $path
     * @param int    $date
     *
     * @return bool
     * @throws FileException
     */
    public function isExpiredDate($path, $date): bool;
}