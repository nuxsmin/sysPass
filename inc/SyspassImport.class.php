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
        if ($this->getUserId() === 0){
            $this->setUserId(Session::getUserId());
        }

        if ($this->getUserGroupId() === 0){
            $this->setUserGroupId(Session::getUserGroupId());
        }

        try {
            $this->addCategories();
            $this->addCustomers();
            $this->getAccountData();
        } catch (SPException $e){
            return false;
        }

        return true;
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     */
    protected function getAccountData()
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
    protected function addCategories()
    {
        foreach ($this->_xml->Categories as $category) {
            $this->_categories[$category['id']] = Category::addCategoryReturnId($category->name, $category->description);
        }
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     */
    protected function addCustomers()
    {
        foreach ($this->_xml->Customers as $customer) {
            $this->_customers[$customer['id']] = Customer::addCustomerReturnId($customer->name, $customer->description);
        }
    }

    /**
     * Añadir una cuenta en sysPass desde XML
     *
     * @return mixed
     */
    protected function addAccount()
    {
        $account = new Account;
        $account->setAccountName($this->getAccountName());
        $account->setAccountCustomerId($this->getCustomerId());
        $account->setAccountCategoryId($this->getCategoryId());
        $account->setAccountLogin($this->getAccountLogin());
        $account->setAccountUrl($this->getAccountUrl());
        $account->setAccountPass($this->getAccountPass());
        $account->setAccountIV($this->getAccountPassIV());
        $account->setAccountNotes($this->getAccountNotes());
        $account->setAccountUserId($this->getUserId());
        $account->setAccountUserGroupId($this->getUserGroupId());

        return $account->createAccount();
    }
}