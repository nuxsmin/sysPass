<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Domain\Import\Services;

use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Import\Ports\FileImportService;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileHandlerInterface;
use SP\Util\Util;

use function SP\__;
use function SP\__u;
use function SP\logger;

/**
 * Clase FileImport encargada el leer archivos para su importación
 *
 * @package SP
 */
final class FileImport implements FileImportService
{
    /**
     * FileImport constructor.
     *
     * @param FileHandlerInterface $fileHandler Datos del archivo a importar
     */
    private function __construct(private readonly FileHandlerInterface $fileHandler)
    {
    }

    /**
     * @throws FileException
     * @throws SPException
     */
    public static function fromRequest(string $filename, RequestInterface $request): FileImportService
    {
        return new self(self::checkFile($request->getFile($filename)));
    }

    /**
     * Leer los datos del archivo.
     *
     * @param array|null $file con los datos del archivo
     *
     * @return FileHandlerInterface
     * @throws ImportException
     * @throws FileException
     */
    private static function checkFile(?array $file): FileHandlerInterface
    {
        if (!is_array($file)) {
            throw FileException::error(
                __u('File successfully uploaded'),
                __u('Please check the web server user permissions')
            );
        }

        try {
            $fileHandler = new FileHandler($file['tmp_name']);
            $fileHandler->checkFileExists();

            if (!in_array($fileHandler->getFileType(), Import::ALLOWED_MIME)) {
                throw ImportException::error(
                    __u('File type not allowed'),
                    sprintf(__('MIME type: %s'), $fileHandler->getFileType())
                );
            }

            return $fileHandler;
        } catch (FileException $e) {
            logger(sprintf('Max. upload size: %s', Util::getMaxUpload()));

            throw FileException::error(
                __u('Internal error while reading the file'),
                __u('Please, check PHP configuration for upload files'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws FileException
     */
    public function getFileType(): string
    {
        return $this->fileHandler->getFileType();
    }

    public function getFilePath(): string
    {
        return $this->fileHandler->getFile();
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
    protected function autodetectEOL(): void
    {
        ini_set('auto_detect_line_endings', true);
    }

    /**
     * Read a CSV file
     *
     * @throws FileException
     */
    public function readFileToArrayFromCsv(string $delimiter): iterable
    {
        $this->autodetectEOL();

        $handler = $this->fileHandler->open();

        while (($fields = fgetcsv($handler, 0, $delimiter)) !== false) {
            yield $fields;
        }

        $this->fileHandler->close();
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

    public function getFileHandler(): FileHandlerInterface
    {
        return $this->fileHandler;
    }
}
