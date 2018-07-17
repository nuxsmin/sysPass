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

namespace SP\Storage;

use RuntimeException;

/**
 * Class FileCachePacked
 *
 * @package SP\Storage
 */
class FileCachePacked implements FileStorageInterface
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
     */
    public function load($path)
    {
        if (!file_exists($path)) {
            throw new RuntimeException(sprintf(__('No es posible leer/escribir el archivo (%s)'), $path));
        }

        if (!($data = file_get_contents($path))) {
            throw new RuntimeException(sprintf(__('Error al leer datos del archivo (%s)'), $path));
        }

        if (!($data = gzuncompress($data))) {
            throw new RuntimeException(sprintf(__('Error al descomprimir datos del archivo (%s)'), $path));
        }

        if (($this->data = unserialize($data)) === false) {
            throw new RuntimeException(__('Error al obtener los datos'));
        }

        $this->loaded = true;

        return $this;
    }

    /**
     * @param string $path
     * @param mixed  $data
     *
     * @return FileStorageInterface
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
     */
    protected function saveData($path, $data)
    {
        $dir = dirname($path);

        if (!is_dir($dir) && mkdir($dir, 0700, true) === false) {
            throw new RuntimeException(sprintf(__('No es posible crear el directorio (%s)'), $dir));
        }

        if (file_exists($path) && !is_writable($path)) {
            throw new RuntimeException(sprintf(__('No es posible leer/escribir el archivo (%s)'), $path));
        }

        if (!($data = gzcompress(serialize($data)))) {
            throw new RuntimeException(sprintf(__('Error al comprimir datos del archivo (%s)'), $path));
        }

        if (!file_put_contents($path, $data)) {
            throw new RuntimeException(sprintf(__('Error al escribir datos en el archivo (%s)'), $path));
        }
    }

    /**
     * @param string $path
     *
     * @return FileStorageInterface
     */
    public function delete($path)
    {
        if (file_exists($path) && !is_writable($path)) {
            throw new RuntimeException(sprintf(__('No es posible leer/escribir el archivo (%s)'), $path));
        }

        if (!unlink($path)) {
            throw new RuntimeException(sprintf(__('Error al eliminar el archivo (%s)'), $path));
        }

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
            throw new RuntimeException((__('Datos no cargados')));
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
     * @return mixed
     */
    public function isExpired($path, $time = 86400)
    {
        // TODO: Implement isExpired() method.
    }
}