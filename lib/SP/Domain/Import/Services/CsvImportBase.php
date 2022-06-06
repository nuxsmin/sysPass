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
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;
use SP\Domain\Account\Services\AccountRequest;
use SP\Domain\Import\FileImportInterface;
use SP\Infrastructure\File\FileException;

defined('APP_ROOT') || die();

/**
 * Clase CsvImportBase para base de clases de importación desde archivos CSV
 *
 * @package SP
 */
abstract class CsvImportBase
{
    use ImportTrait;

    protected int                 $numFields  = 7;
    protected FileImportInterface $fileImport;
    protected EventDispatcher     $eventDispatcher;
    protected array               $categories = [];
    protected array               $clients    = [];

    public function __construct(
        Application $application,
        ImportHelper $importHelper,
        FileImportInterface $fileImport,
        ImportParams $importParams
    ) {
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->accountService = $importHelper->getAccountService();
        $this->categoryService = $importHelper->getCategoryService();
        $this->clientService = $importHelper->getClientService();
        $this->tagService = $importHelper->getTagService();
        $this->fileImport = $fileImport;
        $this->importParams = $importParams;
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas
     *
     * @throws ImportException
     * @throws FileException
     */
    protected function processAccounts(): void
    {
        $line = 0;

        $handler = $this->fileImport->getFileHandler()->open();

        while (($fields = fgetcsv($handler, 0, $this->importParams->getCsvDelimiter())) !== false) {
            $line++;
            $numfields = count($fields);

            // Comprobar el número de campos de la línea
            if ($numfields !== $this->numFields) {
                throw new ImportException(
                    sprintf(__('Wrong number of fields (%d)'), $numfields),
                    SPException::ERROR,
                    sprintf(__('Please, check the CSV file format in line %s'), $line)
                );
            }

            // Asignar los valores del array a variables
            [
                $accountName,
                $clientName,
                $categoryName,
                $url,
                $login,
                $password,
                $notes,
            ] = $fields;

            try {
                if (empty($clientName) || empty($categoryName)) {
                    throw new ImportException('Either client or category name not set');
                }

                // Obtener los ids de cliente y categoría
                $clientId = $this->addClient(new ClientData(null, $clientName));
                $categoryId = $this->addCategory(new CategoryData(null, $categoryName));

                // Crear la nueva cuenta
                $accountRequest = new AccountRequest();
                $accountRequest->name = $accountName;
                $accountRequest->login = $login;
                $accountRequest->clientId = $clientId;
                $accountRequest->categoryId = $categoryId;
                $accountRequest->notes = $notes;
                $accountRequest->url = $url;
                $accountRequest->pass = $password;

                $this->addAccount($accountRequest);

                $this->eventDispatcher->notifyEvent(
                    'run.import.csv.process.account',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDetail(__u('Account imported'), $accountName)
                            ->addDetail(__u('Client'), $clientName)
                    )
                );
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent(
                    'exception',
                    new Event(
                        $e,
                        EventMessage::factory()
                            ->addDetail(__u('Error while importing the account'), $accountName)
                            ->addDetail(__u('Error while processing line'), $line)
                    )
                );
            }
        }

        $this->fileImport->getFileHandler()->close();

        if ($line === 0) {
            throw new ImportException(
                sprintf(__('Wrong number of fields (%d)'), 0),
                SPException::ERROR,
                sprintf(__('Please, check the CSV file format in line %s'), 0)
            );
        }
    }
}