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

namespace SP\Services\Import;

use SP\Http\Request;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Clase FileImport encargada el leer archivos para su importación
 *
 * @package SP
 */
final class FileImport
{
    /**
     * @var \SP\Storage\File\FileHandler
     */
    private $fileHandler;

    /**
     * FileImport constructor.
     *
     * @param \SP\Storage\File\FileHandler $fileHandler Datos del archivo a importar
     */
    private function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    /**
     * @param string  $filename
     * @param Request $request
     *
     * @return FileImport
     * @throws FileException
     */
    public static function fromRequest(string $filename, Request $request)
    {
        if (($file = $request->getFile($filename)) === null) {
            throw new FileException(
                __u('File successfully uploaded'),
                FileException::ERROR,
                __u('Please check the web server user permissions')
            );
        }

        return new self(new FileHandler(self::checkFile($file)));
    }

    /**
     * Leer los datos del archivo.
     *
     * @param array $fileData con los datos del archivo
     *
     * @return string
     * @throws \SP\Storage\File\FileException
     */
    private static function checkFile($fileData): string
    {
        if (!is_array($fileData)) {
            throw new FileException(
                __u('File successfully uploaded'),
                FileException::ERROR,
                __u('Please check the web server user permissions')
            );
        }

        if ($fileData['name']) {
            // Comprobamos la extensión del archivo
            $fileExtension = mb_strtoupper(pathinfo($fileData['name'], PATHINFO_EXTENSION));

            if ($fileExtension !== 'CSV' && $fileExtension !== 'XML') {
                throw new FileException(
                    __u('File type not allowed'),
                    FileException::ERROR,
                    __u('Please, check the file extension')
                );
            }
        }

        // Variables con información del archivo
//        $this->tmpFile = $fileData['tmp_name'];
//        $this->fileType = strtolower($fileData['type']);

        if (!file_exists($fileData['tmp_name']) || !is_readable($fileData['tmp_name'])) {
            // Registramos el máximo tamaño permitido por PHP
            logger('Max. upload size: ' . Util::getMaxUpload());

            throw new FileException(
                __u('Internal error while reading the file'),
                FileException::ERROR,
                __u('Please, check PHP configuration for upload files')
            );
        }

        return $fileData['tmp_name'];
    }

    /**
     * @param string $path
     *
     * @return FileImport
     */
    public static function fromFilesystem(string $path)
    {
        return new self(new FileHandler($path));
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->fileHandler->getFile();
    }

    /**
     * @return string
     * @throws FileException
     */
    public function getFileType()
    {
        return $this->fileHandler->getFileType();
    }

    /**
     * Leer los datos de un archivo subido a un array
     *
     * @throws FileException
     */
    public function readFileToArray(): array
    {
        $this->autodetectEOL();

        return $this->fileHandler->readToArray();
    }

    /**
     * Activar la autodetección de fin de línea
     */
    protected function autodetectEOL()
    {
        ini_set('auto_detect_line_endings', true);
    }

    /**
     * Leer los datos de un archivo subido a una cadena
     *
     * @throws FileException
     */
    public function readFileToString(): string
    {
        $this->autodetectEOL();

        return $this->fileHandler->readToString();
    }

    /**
     * @return \SP\Storage\File\FileHandler
     */
    public function getFileHandler(): FileHandler
    {
        return $this->fileHandler;
    }
}