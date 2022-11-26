<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;

/**
 * Clase FileImport encargada el leer archivos para su importación
 *
 * @package SP
 */
interface FileImportInterface
{
    public function getFilePath(): string;

    /**
     * @throws FileException
     */
    public function getFileType(): string;

    /**
     * Leer los datos de un archivo subido a un array
     *
     * @throws FileException
     */
    public function readFileToArray(): array;

    /**
     * Leer los datos de un archivo subido a una cadena
     *
     * @throws FileException
     */
    public function readFileToString(): string;

    public function getFileHandler(): FileHandlerInterface;
}
