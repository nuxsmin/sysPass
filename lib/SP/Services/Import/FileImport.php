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

namespace SP\Services\Import;

use SP\Core\Exceptions\SPException;
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
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * FileImport constructor.
     *
     * @param FileHandler $fileHandler Datos del archivo a importar
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
     * @throws SPException
     */
    public static function fromRequest(string $filename, Request $request)
    {
        return new self(self::checkFile($request->getFile($filename)));
    }

    /**
     * Leer los datos del archivo.
     *
     * @param array $file con los datos del archivo
     *
     * @return FileHandler
     * @throws FileException
     * @throws SPException
     */
    private static function checkFile($file): FileHandler
    {
        if (!is_array($file)) {
            throw new FileException(
                __u('File successfully uploaded'),
                FileException::ERROR,
                __u('Please check the web server user permissions')
            );
        }

        try {
            $fileHandler = new FileHandler($file['tmp_name']);
            $fileHandler->checkFileExists();

            if (!in_array($fileHandler->getFileType(), ImportService::ALLOWED_MIME)) {
                throw new ImportException(
                    __u('File type not allowed'),
                    ImportException::ERROR,
                    sprintf(__('MIME type: %s'), $fileHandler->getFileType())
                );
            }

            return $fileHandler;
        } catch (FileException $e) {
            logger('Max. upload size: ' . Util::getMaxUpload());

            throw new FileException(
                __u('Internal error while reading the file'),
                FileException::ERROR,
                __u('Please, check PHP configuration for upload files'),
                $e->getCode(),
                $e
            );
        }
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
     * @return FileHandler
     */
    public function getFileHandler(): FileHandler
    {
        return $this->fileHandler;
    }
}