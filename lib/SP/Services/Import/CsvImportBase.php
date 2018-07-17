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

use DI\Container;
use SP\Account\AccountRequest;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Tag\TagService;

defined('APP_ROOT') || die();

/**
 * Clase CsvImportBase para base de clases de importación desde archivos CSV
 *
 * @package SP
 */
abstract class CsvImportBase
{
    use ImportTrait;

    /**
     * @var int
     */
    protected $numFields = 7;
    /**
     * @var array
     */
    protected $mapFields = [];
    /**
     * @var FileImport
     */
    protected $fileImport;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var array
     */
    protected $categories = [];
    /**
     * @var array
     */
    protected $clients = [];

    /**
     * ImportBase constructor.
     *
     * @param Container    $dic
     * @param FileImport   $fileImport
     * @param ImportParams $importParams
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(Container $dic, FileImport $fileImport, ImportParams $importParams)
    {
        $this->fileImport = $fileImport;
        $this->importParams = $importParams;

        $this->accountService = $dic->get(AccountService::class);
        $this->categoryService = $dic->get(CategoryService::class);
        $this->clientService = $dic->get(ClientService::class);
        $this->tagService = $dic->get(TagService::class);
        $this->eventDispatcher = $dic->get(EventDispatcher::class);
    }

    /**
     * @param int $numFields
     */
    public function setNumFields($numFields)
    {
        $this->numFields = $numFields;
    }

    /**
     * @param array $mapFields
     */
    public function setMapFields($mapFields)
    {
        $this->mapFields = $mapFields;
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas
     *
     * @throws ImportException
     * @throws \SP\Storage\FileException
     */
    protected function processAccounts()
    {
        $line = 0;

        $handler = $this->fileImport->getFileHandler()->open();

        while (($fields = fgetcsv($handler, 0, $this->importParams->getCsvDelimiter())) !== false) {
            $line++;
            $numfields = count($fields);

            // Comprobar el número de campos de la línea
            if ($numfields !== $this->numFields) {
                throw new ImportException(
                    sprintf(__('El número de campos es incorrecto (%d)'), $numfields),
                    ImportException::ERROR,
                    sprintf(__('Compruebe el formato del archivo CSV en línea %s'), $line)
                );
            }

            // Asignar los valores del array a variables
            list($accountName, $clientName, $categoryName, $url, $login, $password, $notes) = $fields;

            try {
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

                $this->eventDispatcher->notifyEvent('run.import.csv.process.account',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__u('Cuenta importada'), $accountName)
                        ->addDetail(__u('Cliente'), $clientName))
                );
            } catch (\Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception',
                    new Event($e, EventMessage::factory()
                        ->addDetail(__u('Error importando cuenta'), $accountName)
                        ->addDetail(__u('Error procesando línea'), $line))
                );
            }
        }

        $this->fileImport->getFileHandler()->close();

        if ($line === 0) {
            throw new ImportException(
                sprintf(__('El número de campos es incorrecto (%d)'), 0),
                ImportException::ERROR,
                sprintf(__('Compruebe el formato del archivo CSV en línea %s'), 0)
            );
        }
    }
}