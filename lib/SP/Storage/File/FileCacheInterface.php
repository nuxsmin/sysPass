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
 * Interface FileStorageInterface
 *
 * @package SP\Storage\File;
 */
interface FileCacheInterface
{
    /**
     * @return mixed
     * @throws FileException
     */
    public function load();

    /**
     * @param mixed $data
     *
     * @return FileCacheInterface
     * @throws FileException
     */
    public function save($data);

    /**
     * @return mixed
     */
    public function delete();

    /**
     * Returns whether the file is expired
     *
     * @param int $time
     *
     * @return bool
     */
    public function isExpired($time = 86400): bool;

    /**
     * Returns if the file is expired comparing against a reference date
     *
     * @param int $date
     *
     * @return bool
     * @throws FileException
     */
    public function isExpiredDate($date): bool;

    /**
     * @return bool
     */
    public function exists(): bool;
}