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

use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportStrategyService;
use SP\Domain\Import\Ports\ItemsImportService;
use SP\Domain\Import\Ports\XmlFileService;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;
use SP\Util\Util;

use function SP\__;
use function SP\__u;
use function SP\logger;

/**
 * Class ImportStrategy
 */
final class ImportStrategy extends Service implements ImportStrategyService
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
        private readonly XmlFileService $xmlFile,
    ) {
        parent::__construct($application);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function buildImport(ImportParamsDto $importParams): ItemsImportService
    {
        return $this->fileTypeFactory($importParams);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    private function fileTypeFactory(ImportParamsDto $importParams): ItemsImportService
    {
        $fileHandler = $importParams->getFile();
        $fileType = $this->checkFile($fileHandler);

        switch ($fileType) {
            case 'text/plain':
            case 'text/csv':
                return new CsvImport($this->application, $this->importHelper, $this->crypt, $fileHandler);
            case 'text/xml':
            case 'application/xml':
            return $this->xmlFactory($fileHandler);
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
    private function checkFile(FileHandlerInterface $fileHandler): string
    {
        try {
            $fileHandler->checkIsReadable();

            $fileType = $fileHandler->getFileType();

            if (!in_array($fileType, self::ALLOWED_MIME)) {
                throw ImportException::error(
                    __u('File type not allowed'),
                    sprintf(__('MIME type: %s'), $fileType)
                );
            }

            return $fileType;
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
    protected function xmlFactory(FileHandlerInterface $fileHandler): ItemsImportService
    {
        $xmlFile = $this->xmlFile->builder($fileHandler);

        switch ($xmlFile->detectFormat()) {
            case XmlFormat::Syspass:
                return new SyspassImport(
                    $this->application,
                    $this->importHelper,
                    $this->crypt,
                    $xmlFile->getDocument()
                );
            case XmlFormat::Keepass:
                return new KeepassImport(
                    $this->application,
                    $this->importHelper,
                    $this->crypt,
                    $xmlFile->getDocument()
                );
        }

        throw ImportException::error(__u('Format not detected'));
    }
}
