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

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Services\Service;
use SP\Storage\File\FileException;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas.
 */
final class ImportService extends Service
{
    const ALLOWED_MIME = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.mes-excel',
        'text/plain',
        'text/csv',
        'application/xml',
        'text/xml'
    ];

    /**
     * @var ImportParams
     */
    protected $importParams;
    /**
     * @var FileImport
     */
    protected $fileImport;

    /**
     * Iniciar la importación de cuentas.
     *
     * @param ImportParams $importParams
     * @param FileImport   $fileImport
     *
     * @return int Returns the total number of imported items
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function doImport(ImportParams $importParams, FileImport $fileImport)
    {
        $this->importParams = $importParams;
        $this->fileImport = $fileImport;

        return $this->transactionAware(function () {
            return $this->selectImportType()
                ->doImport()
                ->getCounter();
        });
    }

    /**
     * @return ImportInterface
     * @throws ImportException
     * @throws FileException
     */
    protected function selectImportType()
    {
        $fileType = $this->fileImport->getFileType();

        switch ($fileType) {
            case 'text/plain':
                return new CsvImport($this->dic, $this->fileImport, $this->importParams);
            case 'text/xml':
            case 'application/xml':
                return new XmlImport($this->dic, new XmlFileImport($this->fileImport), $this->importParams);
        }

        throw new ImportException(
            sprintf(__('Mime type not supported ("%s")'), $fileType),
            ImportException::ERROR,
            __u('Please, check the file format')
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        set_time_limit(0);
    }
}