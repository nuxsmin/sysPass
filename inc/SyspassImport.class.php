<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de importar cuentas desde KeePass
 */
class SyspassImport extends XmlImportBase
{
    /**
     * Iniciar la importación desde KeePass
     *
     * @param \SimpleXMLElement $xml
     * @throws SPException
     * @return bool
     */
    public function addAccounts(\SimpleXMLElement $xml)
    {
        try {
            $this->getAccountData($xml->Accounts);
            $this->getCategories($xml->Categories);
            $this->getCustomers($xml->Customers);
        } catch (SPException $e){
            return false;
        }

        return true;
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param \SimpleXMLElement $entries  El objeto XML del nodo de cuentas
     */
    protected function getAccountData(\SimpleXMLElement $entries)
    {
        foreach ($entries as $entry) {
            $account = $entry->Account;

            $this->setAccountName($account->name);
            $this->setAccountLogin($account->login);
            $this->setCategoryId($account->categoryId);
            $this->setCustomerId($account->customerId);
            $this->setAccountUrl($account->url);
            $this->setAccountLogin($account->login);
            $this->setAccountPass($account->pass);
            $this->setAccountPassIV($account->passiv);
            $this->setAccountNotes($account->notes);

            error_log($this->getAccountName());

//            Import::addAccountData($accountData);
        }
    }

    /**
     * Obtener las categorías.
     *
     * @param \SimpleXMLElement $entries El objeto XML del nodo categorias
     */
    protected function getCategories(\SimpleXMLElement $entries)
    {
        foreach ($entries->Category as $category) {
            $this->setCategoryId($category['id']);
            $this->setCategoryName($category->name);

            Category::addCategory();
        }
    }

    /**
     * Obtener los clientes.
     *
     * @param \SimpleXMLElement $entries El objeto XML del nodo clientes
     */
    protected function getCustomers(\SimpleXMLElement $entries)
    {
        foreach ($entries->Customer as $customer) {
            $this->setCustomerId($customer['id']);
            $this->setCustomerName($customer->name);
        }
    }
}