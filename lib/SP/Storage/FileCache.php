<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

namespace SP\Storage;

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
     * @throws FileException
     */
    public function load($path)
    {
        if (!file_exists($path)) {
            throw new FileException(sprintf(__('No es posible leer el archivo (%s)'), $path));
        }

        if (!($data = file_get_contents($path))) {
            throw new FileException(sprintf(__('Error al leer datos del archivo: %s'), $path));
        }
        return unserialize($data);
    }

    /**
     * @param string $path
     * @param mixed  $data
     * @return FileStorageInterface
     * @throws FileException
     */
    public function save($path, $data)
    {
        $dir = dirname($path);

        if (!is_dir($dir) && mkdir($dir, 0700, true) === false) {
            throw new FileException(sprintf(__('No es posible crear el directorio (%s)'), $dir));
        }

        if (file_exists($path) && !is_writable($path)) {
            throw new FileException(sprintf(__('No es posible escribir en el archivo (%s)'), $path));
        }

        if (!file_put_contents($path, serialize($data))) {
            throw new FileException(sprintf(__('No es posible escribir en el archivo (%s)'), $path));
        }

        return $this;
    }

    /**
     * @param string $path
     *
     * @return FileStorageInterface
     * @throws FileException
     */
    public function delete($path)
    {
        if (file_exists($path) && !is_writable($path)) {
            throw new FileException(sprintf(__('No es posible abrir el archivo (%s)'), $path));
        }

        if (!unlink($path)) {
            throw new FileException(sprintf(__('Error al eliminar el archivo (%s)'), $path));
        }

        return $this;
    }
}