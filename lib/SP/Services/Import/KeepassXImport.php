<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

use SimpleXMLElement;
use SP\Account\AccountRequest;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde KeePassX
 *
 * @todo Use xml
 */
class KeepassXImport extends XmlImportBase implements ImportInterface
{
    /**
     * @var int
     */
    protected $clientId;

    /**
     * Iniciar la importación desde KeePassX.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @return ImportInterface
     */
    public function doImport()
    {
        $this->clientId = $this->addClient(new ClientData(null, 'KeePassX'));

        $this->processCategories($this->xmlDOM);

        return $this;
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @param SimpleXMLElement $xml con objeto XML del archivo de KeePass
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function processCategories(SimpleXMLElement $xml)
    {
        foreach ($xml as $node) {
            if ($node->group) {
                foreach ($node->group as $group) {
                    // Analizar grupo
                    if ($node->group->entry) {
                        // Crear la categoría
                        // Crear cuentas
                        $this->processAccounts($group->entry, $this->addCategory(new CategoryData(null, $group->title, 'KeePassX')));
                    }

                    if ($group->group) {
                        // Analizar subgrupo
                        $this->processCategories($group);
                    }
                }
            }

            if ($node->entry) {
                // Crear cuentas
                $this->processAccounts($node->entry, $this->addCategory(new CategoryData(null, $node->title, 'KeePassX')));
            }
        }
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param SimpleXMLElement $entries    El objeto XML con las entradas
     * @param int              $categoryId Id de la categoría
     */
    protected function processAccounts(SimpleXMLElement $entries, $categoryId)
    {
        foreach ($entries as $entry) {
            $name = isset($entry->title) ? (string)$entry->title : '';
            $password = isset($entry->password) ? (string)$entry->password : '';
            $url = isset($entry->url) ? (string)$entry->url : '';
            $notes = isset($entry->comment) ? (string)$entry->comment : '';
            $username = isset($entry->username) ? (string)$entry->username : '';

            $accountRequest = new AccountRequest();
            $accountRequest->pass = $password;
            $accountRequest->notes = $notes;
            $accountRequest->name = $name;
            $accountRequest->url = $url;
            $accountRequest->login = $username;
            $accountRequest->clientId = $this->clientId;
            $accountRequest->categoryId = $categoryId;

            try {
                $this->addAccount($accountRequest);
            } catch (\Exception $e) {
                processException($e);
            }
        }
    }
}