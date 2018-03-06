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
 * Class FileHandler
 * @package SP\Storage
 */
class FileHandler
{
    /**
     * @var string
     */
    protected $file;
    /**
     * @var
     */
    protected $handle;

    /**
     * FileHandler constructor.
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @param $data
     * @return FileHandler
     * @throws FileException
     */
    public function write($data)
    {
        if ($this->handle === null) {
            $this->open('w');
        }

        if (fwrite($this->handle, $data) === false) {
            throw new FileException(sprintf(__('No es posible escribir en el archivo (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * @param $mode
     * @return resource
     * @throws FileException
     */
    public function open($mode)
    {
        if (($this->handle = fopen($this->file, $mode)) === false) {
            throw new FileException(sprintf(__('No es posible abrir el archivo (%s)'), $this->file));
        }

        return $this->handle;
    }

    /**
     * @throws FileException
     */
    public function close()
    {
        if (fclose($this->handle) === false) {
            throw new FileException(sprintf(__('No es posible cerrar el archivo (%s)'), $this->file));
        }

        return $this;
    }
}