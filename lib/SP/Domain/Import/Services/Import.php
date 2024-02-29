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
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportService;
use SP\Domain\Import\Ports\ItemsImportService;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;
use SP\Util\Util;

use function SP\__;
use function SP\__u;
use function SP\logger;

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
        private readonly Application    $application,
        private readonly ImportHelper   $importHelper,
        private readonly CryptInterface $crypt,
        private readonly Repository     $repository
    ) {
        parent::__construct($application);
    }


    /**
     * Iniciar la importación de cuentas.
     *
     * @return ItemsImportService Returns the total number of imported items
     * @throws Exception
     */
    public function doImport(ImportParamsDto $importParams): ItemsImportService
    {
        set_time_limit(0);

        return $this->repository->transactionAware(
            fn(): ItemsImportService => $this->factory($importParams)->doImport($importParams),
            $this
        );
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    protected function factory(ImportParamsDto $importParams): ItemsImportService
    {
        $fileHandler = $importParams->getFile();
        $this->checkFile($fileHandler);
        $fileType = $fileHandler->getType();

        switch ($fileType) {
            case 'text/plain':
                return new CsvImport($this->application, $this->importHelper, $this->crypt, $fileHandler);
            case 'text/xml':
            case 'application/xml':
            return $this->xmlFactory($importParams);
        }

        throw ImportException::error(
            sprintf(__('Mime type not supported ("%s")'), $fileType),
            __u('Please, check the file format')
        );
    }

    /**
     * @throws FileException
     * @throws ImportException
     */
    private function checkFile(FileHandlerInterface $fileHandler): void
    {
        try {
            $fileHandler->checkIsReadable();

            if (!in_array($fileHandler->getFileType(), self::ALLOWED_MIME)) {
                throw ImportException::error(
                    __u('File type not allowed'),
                    sprintf(__('MIME type: %s'), $fileHandler->getFileType())
                );
            }
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
     * @throws ImportException
     * @throws FileException
     */
    protected function xmlFactory(ImportParamsDto $importParams): ItemsImportService
    {
        $xmlFileService = XmlFile::builder($importParams->getFile());

        switch ($xmlFileService->detectFormat()) {
            case XmlFormat::Syspass:
                return new SyspassImport(
                    $this->application,
                    $this->importHelper,
                    $this->crypt,
                    $xmlFileService
                );
            case XmlFormat::Keepass:
                return new KeepassImport(
                    $this->application,
                    $this->importHelper,
                    $this->crypt,
                    $xmlFileService
                );
        }

        throw ImportException::error(__u('Format not detected'));
    }
}
