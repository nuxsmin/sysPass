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

use Exception;
use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigServiceInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Ports\ImportServiceInterface;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\File\FileException;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas.
 */
final class ImportService extends Service implements ImportServiceInterface
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

    private ?ImportParams          $importParams = null;
    private ?FileImportInterface   $fileImport   = null;
    private Application            $application;
    private ImportHelper           $importHelper;
    private ConfigServiceInterface $configService;
    private DatabaseInterface      $database;

    public function __construct(
        Application $application,
        ImportHelper $importHelper,
        ConfigServiceInterface $configService,
        DatabaseInterface $database
    ) {
        parent::__construct($application);

        $this->application = $application;
        $this->importHelper = $importHelper;
        $this->configService = $configService;
        $this->database = $database;

        set_time_limit(0);
    }


    /**
     * Iniciar la importación de cuentas.
     *
     * @return int Returns the total number of imported items
     * @throws Exception
     */
    public function doImport(ImportParams $importParams, FileImportInterface $fileImport): int
    {
        $this->importParams = $importParams;
        $this->fileImport = $fileImport;

        return $this->transactionAware(
            fn() => $this->selectImportType()->doImport()->getCounter(),
            $this->database
        );
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    protected function selectImportType(): ImportInterface
    {
        $fileType = $this->fileImport->getFileType();

        switch ($fileType) {
            case 'text/plain':
                return new CsvImport(
                    $this->application,
                    $this->importHelper,
                    $this->fileImport,
                    $this->importParams
                );
            case 'text/xml':
            case 'application/xml':
                return new XmlImport(
                    $this->application,
                    $this->importHelper,
                    $this->configService,
                    new XmlFileImport($this->fileImport),
                    $this->importParams
                );
        }

        throw new ImportException(
            sprintf(__('Mime type not supported ("%s")'), $fileType),
            SPException::ERROR,
            __u('Please, check the file format')
        );
    }
}
