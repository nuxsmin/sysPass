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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de importar cuentas desde KeePass
 */
class KeepassImport extends XmlImportBase
{
    /**
     * @var int
     */
    protected $customerId = 0;

    /**
     * Iniciar la importación desde KeePass
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function doImport()
    {
        $customerData = new CustomerData(null, 'KeePass');
        $this->addCustomer($customerData);

        $this->customerId = $customerData->getCustomerId();

        $this->processCategories($this->xml->Root->Group);
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @param SimpleXMLElement $xml El objeto XML del archivo de KeePass
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function processCategories(SimpleXMLElement $xml)
    {
        foreach ($xml as $node) {
            if ($node->Group) {
                foreach ($node->Group as $group) {
                    // Analizar grupo
                    if ($node->Group->Entry) {
                        // Crear la categoría
                        $CategoryData = new CategoryData(null, $group->Name, 'KeePass');
                        $this->addCategory($CategoryData);

                        // Crear cuentas
                        $this->processAccounts($group->Entry, $CategoryData->getCategoryId());
                    }

                    if ($group->Group) {
                        // Analizar subgrupo
                        $this->processCategories($group);
                    }
                }
            }

            if ($node->Entry) {
                // Crear la categoría
                $CategoryData = new CategoryData(null, $node->Name, 'KeePass');
                $this->addCategory($CategoryData);

                // Crear cuentas
                $this->processAccounts($node->Entry, $CategoryData->getCategoryId());
            }
        }
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param SimpleXMLElement $entries    El objeto XML con las entradas
     * @param int              $categoryId Id de la categoría
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function processAccounts(SimpleXMLElement $entries, $categoryId)
    {
        foreach ($entries as $entry) {
            $AccountData = new AccountExtData();

            foreach ($entry->String as $account) {
                $value = isset($account->Value) ? (string)$account->Value : '';

                switch ($account->Key) {
                    case 'Notes':
                        $AccountData->setAccountNotes($value);
                        break;
                    case 'Password':
                        $AccountData->setAccountPass($value);
                        break;
                    case 'Title':
                        $AccountData->setAccountName($value);
                        break;
                    case 'URL':
                        $AccountData->setAccountUrl($value);
                        break;
                    case 'UserName':
                        $AccountData->setAccountLogin($value);
                        break;
                }
            }

            $AccountData->setAccountCategoryId($categoryId);
            $AccountData->setAccountCustomerId($this->customerId);

            $this->addAccount($AccountData);
        }
    }
}