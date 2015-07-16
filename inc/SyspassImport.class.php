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
            if ($this->detectEncrypted() && !is_null($this->getImportPass())) {
                $this->processEncrypted();
            }
//            $this->processCategories();
//            $this->processCustomers();
            $this->processAccounts();
        } catch (SPException $e) {
            return false;
        }

        return true;
    }

    protected function detectEncrypted()
    {
        return ($this->_xmlDOM->getElementsByTagName('Encrypted')->length > 0);
    }

    protected function processEncrypted()
    {
        foreach ($this->_xmlDOM->getElementsByTagName('Data') as $node) {
            error_log($node->getAttribute('iv'));

            $data = base64_decode($node->nodeValue);
            $iv = base64_decode($node->getAttribute('iv'));

            $newXmlData = new \DOMDocument();
//            $newXmlData->preserveWhiteSpace = true;
            $newXmlData->loadXML(Crypt::getDecrypt($data, $this->getImportPass(), $iv));

            $this->_xmlDOM->getElementsByTagName('Root')->item(0)->appendChild($newXmlData);
        }
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas.
     */
    protected function processAccounts()
    {
        foreach ($this->_xmlDOM->getElementsByTagName('Account') as $account) {
            foreach ($account->childNodes as $attribute) {
                switch ($attribute->nodeName) {
                    case 'name';
                        $this->setAccountName($attribute->nodeValue);
                        break;
                    case 'login';
                        $this->setAccountLogin($attribute->nodeValue);
                        break;
                    case 'categories';
                        $this->setCategoryId($attribute->nodeValue);
                        break;
                    case 'customers';
                        $this->setCustomerId($attribute->nodeValue);
                        break;
                    case 'url';
                        $this->setAccountUrl($attribute->nodeValue);
                        break;
                    case 'pass';
                        $this->setAccountPass($attribute->nodeValue);
                        break;
                    case 'passiv';
                        $this->setAccountPassIV($attribute->nodeValue);
                        break;
                    case 'notes';
                        $this->setAccountNotes($attribute->nodeValue);
                        break;
                }
            }

//            $this->setAccountName($account->name);
//            $this->setAccountLogin($account->login);
//            $this->setCategoryId($this->_categories[(int)$account->categoryId]);
//            $this->setCustomerId($this->_customers[(int)$account->customerId]);
//            $this->setAccountUrl($account->url);
//            $this->setAccountPass(base64_decode($account->pass));
//            $this->setAccountPassIV(base64_decode($account->passiv));
//            $this->setAccountNotes($account->notes);

//            $this->addAccount();
        }
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     */
    protected function processCategories()
    {
        foreach ($this->_xml->Categories->Category as $category) {
            $this->setCategoryName($category->name);
            $this->setCategoryDescription($category->description);

            $this->_categories[(int)$category['id']] = $this->addCategory();
        }
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     */
    protected function processCustomers()
    {
        foreach ($this->_xml->Customers->Customer as $customer) {
            $this->setCustomerName($customer->name);
            $this->setCustomerDescription($customer->description);

            $this->_customers[(int)$customer['id']] = $this->addCustomer();
        }
    }
}