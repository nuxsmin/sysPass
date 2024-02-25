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

use Exception;
use SP\Core\Application;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Import\Ports\FileImportService;
use SP\Domain\Import\Ports\ImportParams;
use SP\Domain\Import\Ports\ImportService;
use SP\Infrastructure\File\FileException;

use function SP\__;
use function SP\__u;

/**
 * Esta clase es la encargada de importar cuentas.
 */
final class Import extends Service implements ImportService
{
    public const ALLOWED_MIME = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/plain',
        'text/csv',
        'text/x-csv',
        'application/xml',
        'text/xml',
    ];

    public function __construct(
        private readonly Application       $application,
        private readonly ImportHelper      $importHelper,
        private readonly FileImportService $fileImport,
        private readonly CryptInterface    $crypt,
        private readonly Repository        $repository
    ) {
        parent::__construct($application);
    }


    /**
     * Iniciar la importación de cuentas.
     *
     * @return ImportService Returns the total number of imported items
     * @throws Exception
     */
    public function doImport(ImportParams $importParams): ImportService
    {
        set_time_limit(0);

        return $this->repository->transactionAware(fn() => $this->factory()->doImport($importParams), $this);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    protected function factory(): ImportService
    {
        $fileType = $this->fileImport->getFileType();

        switch ($fileType) {
            case 'text/plain':
                return new CsvImport($this->application, $this->importHelper, $this->crypt, $this->fileImport);
            case 'text/xml':
            case 'application/xml':
                return new XmlImport(
                    $this->application,
                    $this->importHelper,
                    new XmlFile($this->fileImport->getFileHandler()),
                    $this->crypt
                );
        }

        throw ImportException::error(
            sprintf(__('Mime type not supported ("%s")'), $fileType),
            __u('Please, check the file format')
        );
    }

    /**
     * @throws ImportException
     */
    public function getCounter(): int
    {
        throw ImportException::info(__u('Not implemented'));
    }
}
