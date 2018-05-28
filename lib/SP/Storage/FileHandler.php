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

/**
 * Class FileHandler
 *
 * @package SP\Storage
 */
class FileHandler
{
    const CHUNK_LENGTH = 8192;
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
     *
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * Writes data into file
     *
     * @param $data
     * @return FileHandler
     * @throws FileException
     */
    public function write($data)
    {
        if (!is_resource($this->handle)) {
            $this->open('wb');
        }

        if (fwrite($this->handle, $data) === false) {
            throw new FileException(sprintf(__('No es posible escribir en el archivo (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Opens the file
     *
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
     * Reads data from file into a string
     *
     * @return string Data read from file
     * @throws FileException
     */
    public function readString()
    {
        if (($data = file_get_contents($this->file)) === false) {
            throw new FileException(sprintf(__('No es posible leer desde el archivo (%s)'), $this->file));
        }

        return $data;
    }

    /**
     * Reads data from file
     *
     * @return string Data read from file
     * @throws FileException
     */
    public function read()
    {
        if (!is_resource($this->handle)) {
            $this->open('rb');
        }

        $data = '';

        while (!feof($this->handle)) {
            $data .= fread($this->handle, self::CHUNK_LENGTH);
        }

        $this->close();

        return $data;
    }

    /**
     * Closes the file
     *
     * @throws FileException
     * @return FileHandler
     */
    public function close()
    {
        if (!is_resource($this->handle) || fclose($this->handle) === false) {
            throw new FileException(sprintf(__('No es posible cerrar el archivo (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Checks if the file is writable
     *
     * @throws FileException
     * @return FileHandler
     */
    public function checkIsWritable()
    {
        if (!is_writable($this->file)) {
            throw new FileException(sprintf(__('No es posible escribir el archivo (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Checks if the file is readable
     *
     * @throws FileException
     * @return FileHandler
     */
    public function checkIsReadable()
    {
        if (!is_readable($this->file)) {
            throw new FileException(sprintf(__('No es posible leer el archivo (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Checks if the file exists
     *
     * @throws FileException
     * @return FileHandler
     */
    public function checkFileExists()
    {
        if (!file_exists($this->file)) {
            throw new FileException(sprintf(__('Archivo no encontrado (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @param bool $isExceptionOnZero
     * @return int
     * @throws FileException
     */
    public function getFileSize($isExceptionOnZero = false): int
    {
        $size = filesize($this->file);

        if ($size === false || ($isExceptionOnZero === true && $size === 0)) {
            throw new FileException(sprintf(__('No es posible leer el archivo (%s)'), $this->file));
        }

        return $size;
    }

    /**
     * Clears the stat cache for the given file
     *
     * @return FileHandler
     */
    public function clearCache()
    {
        clearstatcache(true, $this->file);

        return $this;
    }

    /**
     * Deletes a file
     *
     * @return FileHandler
     * @throws FileException
     */
    public function delete()
    {
        if (@unlink($this->file) === false) {
            throw new FileException(sprintf(__('No es posible eliminar el archivo (%s)'), $this->file));
        }

        return $this;
    }
}