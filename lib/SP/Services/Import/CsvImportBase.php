<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Account\AccountRequest;
use SP\Bootstrap;
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
     * ImportBase constructor.
     *
     * @param FileImport   $fileImport
     * @param ImportParams $importParams
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(FileImport $fileImport, ImportParams $importParams)
    {
        $this->fileImport = $fileImport;
        $this->importParams = $importParams;

        $dic = Bootstrap::getContainer();
        $this->accountService = $dic->get(AccountService::class);
        $this->categoryService = $dic->get(CategoryService::class);
        $this->clientService = $dic->get(ClientService::class);
        $this->tagService = $dic->get(TagService::class);
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
     */
    protected function processAccounts()
    {
        $line = 0;

        foreach ($this->fileImport->getFileContent() as $data) {
            $line++;
            $fields = str_getcsv($data, $this->importParams->getCsvDelimiter(), '"');
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
                $clientData = new ClientData(null, $clientName);
                $this->addClient($clientData);

                $categoryData = new CategoryData(null, $categoryName);
                $this->addCategory($categoryData);

                // Crear la nueva cuenta
                $accountRequest = new AccountRequest();
                $accountRequest->name = $accountName;
                $accountRequest->login = $login;
                $accountRequest->clientId = $clientData->getId();
                $accountRequest->categoryId = $categoryData->getId();
                $accountRequest->notes = $notes;
                $accountRequest->url = $url;
                $accountRequest->pass = $password;

                $this->addAccount($accountRequest);
            } catch (\Exception $e) {
                processException($e);
//                $this->LogMessage->addDetails(__('Error importando cuenta', false), $accountName);
//                $this->LogMessage->addDetails(__('Error procesando línea', false), $line);
//                $this->LogMessage->addDetails(__('Error', false), $e->getMessage());
            }
        }
    }
}