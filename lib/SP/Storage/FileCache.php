<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Storage;

use RuntimeException;

/**
 * Class FileCache
 *
 * @package SP\Storage
 */
class FileCache implements FileStorageInterface
{
    /**
     * @param string $path
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function load($path)
    {
        if (!file_exists($path)) {
            throw new RuntimeException(sprintf(__('No es posible leer/escribir el archivo: %s'), $path));
        }

        if (!($data = file_get_contents($path))) {
            throw new RuntimeException(sprintf(__('Error al leer datos del archivo: %s'), $path));
        }
        return unserialize($data);
    }

    /**
     * @param string $path
     * @param mixed  $data
     * @return FileStorageInterface
     */
    public function save($path, $data)
    {
        if (file_exists($path) && !is_writable($path)) {
            throw new RuntimeException(sprintf(__('No es posible leer/escribir el archivo: %s'), $path));
        }

        if (!file_put_contents($path, serialize($data))) {
            throw new RuntimeException(sprintf(__('Error al escribir datos en el archivo: %s'), $path));
        }

        return $this;
    }
}