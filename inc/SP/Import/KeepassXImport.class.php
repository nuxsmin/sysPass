<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Import;

use SimpleXMLElement;
use SP\DataModel\AccountExtData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde KeePassX
 */
class KeepassXImport extends ImportBase
{
    use XmlImportTrait;

    /**
     * @var int
     */
    protected $customerId = 0;

    /**
     * Iniciar la importación desde KeePassX.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @return bool
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function doImport()
    {
        $customerData = new CustomerData(null, 'KeePassX');
        $this->addCustomer($customerData);

        $this->customerId = $customerData->getCustomerId();

        $this->processCategories($this->xml);
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
                        $CategoryData = new CategoryData(null, $group->title, 'KeePassX');
                        $this->addCategory($CategoryData);

                        // Crear cuentas
                        $this->processAccounts($group->entry, $CategoryData->getCategoryId());
                    }

                    if ($group->group) {
                        // Analizar subgrupo
                        $this->processCategories($group);
                    }
                }
            }

            if ($node->entry) {
                $CategoryData = new CategoryData(null, $node->title, 'KeePassX');
                $this->addCategory($CategoryData);

                // Crear cuentas
                $this->processAccounts($node->entry, $CategoryData->getCategoryId());
            }
        }
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param SimpleXMLElement $entries El objeto XML con las entradas
     * @param int $categoryId Id de la categoría
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function processAccounts(SimpleXMLElement $entries, $categoryId)
    {
        foreach ($entries as $entry) {
            $name = isset($entry->title) ? (string)$entry->title : '';
            $password = isset($entry->password) ? (string)$entry->password : '';
            $url = isset($entry->url) ? (string)$entry->url : '';
            $notes = isset($entry->comment) ? (string)$entry->comment : '';
            $username = isset($entry->username) ? (string)$entry->username : '';

            $AccountData = new AccountExtData();
            $AccountData->setAccountPass($password);
            $AccountData->setAccountNotes($notes);
            $AccountData->setAccountName($name);
            $AccountData->setAccountUrl($url);
            $AccountData->setAccountLogin($username);
            $AccountData->setAccountCustomerId($this->customerId);
            $AccountData->setAccountCategoryId($categoryId);

            $this->addAccount($AccountData);
        }
    }
}