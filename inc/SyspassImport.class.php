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
 * Esta clase es la encargada de importar cuentas desde sysPass
 */
class SyspassImport extends XmlImportBase
{
    /**
     * Mapeo de categorías.
     *
     * @var array
     */
    private $_categories = array();
    /**
     * Mapeo de clientes.
     *
     * @var array
     */
    private $_customers = array();

    /**
     * Iniciar la importación desde sysPass.
     *
     * @throws SPException
     * @return bool
     */
    public function doImport()
    {
        try {
            $this->processCategories();
            $this->processCustomers();
            $this->processAccounts();
        } catch (SPException $e){
            return false;
        }

        return true;
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas.
     */
    protected function processAccounts()
    {
        foreach ($this->_xml->Accounts as $entry) {
            $account = $entry->Account;

            $this->setAccountName($account->name);
            $this->setAccountLogin($account->login);
            $this->setCategoryId($this->_categories[$account->categoryId]);
            $this->setCustomerId($this->_customers[$account->customerId]);
            $this->setAccountUrl($account->url);
            $this->setAccountLogin($account->login);
            $this->setAccountPass($account->pass);
            $this->setAccountPassIV($account->passiv);
            $this->setAccountNotes($account->notes);

            $this->addAccount();
        }
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     */
    protected function processCategories()
    {
        foreach ($this->_xml->Categories as $category) {
            $this->setCustomerName($category->name);
            $this->setCategoryDescription($category->description);

            $this->_categories[$category['id']] = $this->addCategory();
        }
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     */
    protected function processCustomers()
    {
        foreach ($this->_xml->Customers as $customer) {
            $this->setCustomerName($customer->name);
            $this->setCustomerDescription($customer->description);

            $this->_customers[$customer['id']] = $this->addCustomer();
        }
    }
}