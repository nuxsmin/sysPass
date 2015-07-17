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
     */
    public function doImport()
    {
        try {
            if ($this->detectEncrypted() && !is_null($this->getImportPass())) {
                $this->processEncrypted();
            }
            $this->processCategories();
            $this->processCustomers();
            $this->processAccounts();
        } catch (SPException $e) {
            throw $e;
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_CRITICAL, $e->getMessage());
        }
    }

    /**
     * Verificar si existen datos encriptados
     *
     * @return bool
     */
    protected function detectEncrypted()
    {
        return ($this->_xmlDOM->getElementsByTagName('Encrypted')->length > 0);
    }

    /**
     * Procesar los datos encriptados y añadirlos al árbol DOM desencriptados
     */
    protected function processEncrypted()
    {
        foreach ($this->_xmlDOM->getElementsByTagName('Data') as $node) {
            $data = base64_decode($node->nodeValue);
            $iv = base64_decode($node->getAttribute('iv'));

            $xmlDecrypted = Crypt::getDecrypt($data, $this->getImportPass(), $iv);

            $newXmlData = new \DOMDocument();
//            $newXmlData->preserveWhiteSpace = true;
            $newXmlData->loadXML($xmlDecrypted);
            $newNode = $this->_xmlDOM->importNode($newXmlData->documentElement, TRUE);

            $this->_xmlDOM->documentElement->appendChild($newNode);
        }

        // Eliminar los datos encriptados tras desencriptar los mismos
        if ($this->_xmlDOM->getElementsByTagName('Data')->length > 0) {
            $nodeData = $this->_xmlDOM->getElementsByTagName('Encrypted')->item(0);
            $nodeData->parentNode->removeChild($nodeData);
        }
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     */
    protected function processCategories()
    {
        if ($this->_xmlDOM->getElementsByTagName('Categories')->length === 0) {
            throw new SPException(SPException::SP_WARNING, _('Formato de XML inválido'), _('No hay categorías para importar'));
        }

        foreach ($this->_xmlDOM->getElementsByTagName('Category') as $category) {
            foreach ($category->childNodes as $node) {
                switch ($node->nodeName) {
                    case 'name':
                        $this->setCategoryName($node->nodeValue);
                        break;
                    case 'description':
                        $this->setCategoryName($node->nodeValue);
                        break;
                }
            }

            $this->_categories[$category->getAttribute('id')] = $this->addCategory();
        }
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     */
    protected function processCustomers()
    {
        if ($this->_xmlDOM->getElementsByTagName('Customers')->length === 0) {
            throw new SPException(SPException::SP_WARNING, _('Formato de XML inválido'), _('No hay clientes para importar'));
        }

        foreach ($this->_xmlDOM->getElementsByTagName('Customer') as $customer) {
            foreach ($customer->childNodes as $node) {
                switch ($node->nodeName) {
                    case 'name':
                        $this->setCustomerName($node->nodeValue);
                        break;
                    case 'description':
                        $this->setCustomerDescription($node->nodeValue);
                        break;
                }
            }

            $this->_customers[$customer->getAttribute('id')] = $this->addCustomer();
        }
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas.
     */
    protected function processAccounts()
    {
        if ($this->_xmlDOM->getElementsByTagName('Accounts')->length === 0) {
            throw new SPException(SPException::SP_WARNING, _('Formato de XML inválido'), _('No hay cuentas para importar'));
        }

        foreach ($this->_xmlDOM->getElementsByTagName('Account') as $account) {
            foreach ($account->childNodes as $node) {
                switch ($node->nodeName) {
                    case 'name';
                        $this->setAccountName($node->nodeValue);
                        break;
                    case 'login';
                        $this->setAccountLogin($node->nodeValue);
                        break;
                    case 'categoryId';
                        $this->setCategoryId($this->_categories[(int)$node->nodeValue]);
                        break;
                    case 'customerId';
                        $this->setCustomerId($this->_customers[(int)$node->nodeValue]);
                        break;
                    case 'url';
                        $this->setAccountUrl($node->nodeValue);
                        break;
                    case 'pass';
                        $this->setAccountPass(base64_decode($node->nodeValue));
                        break;
                    case 'passiv';
                        $this->setAccountPassIV(base64_decode($node->nodeValue));
                        break;
                    case 'notes';
                        $this->setAccountNotes($node->nodeValue);
                        break;
                }
            }

            $this->addAccount();
        }
    }
}