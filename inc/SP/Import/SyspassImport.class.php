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

namespace SP\Import;

use SP\Core\Crypt;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;

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
    private $categories = [];
    /**
     * Mapeo de clientes.
     *
     * @var array
     */
    private $customers = [];

    /**
     * Iniciar la importación desde sysPass.
     *
     * @throws SPException
     */
    public function doImport()
    {
        try {
            if ($this->detectEncrypted() && null !== $this->getImportPass()) {
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
        return ($this->xmlDOM->getElementsByTagName('Encrypted')->length > 0);
    }

    /**
     * Procesar los datos encriptados y añadirlos al árbol DOM desencriptados
     */
    protected function processEncrypted()
    {
        foreach ($this->xmlDOM->getElementsByTagName('Data') as $node) {
            /** @var $node \DOMElement */
            $data = base64_decode($node->nodeValue);
            $iv = base64_decode($node->getAttribute('iv'));

            $xmlDecrypted = Crypt::getDecrypt($data, $iv, $this->getImportPass());

            $newXmlData = new \DOMDocument();
//            $newXmlData->preserveWhiteSpace = true;
            $newXmlData->loadXML($xmlDecrypted);
            $newNode = $this->xmlDOM->importNode($newXmlData->documentElement, TRUE);

            $this->xmlDOM->documentElement->appendChild($newNode);
        }

        // Eliminar los datos encriptados tras desencriptar los mismos
        if ($this->xmlDOM->getElementsByTagName('Data')->length > 0) {
            $nodeData = $this->xmlDOM->getElementsByTagName('Encrypted')->item(0);
            $nodeData->parentNode->removeChild($nodeData);
        }
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function processCategories()
    {
        if ($this->xmlDOM->getElementsByTagName('Categories')->length === 0) {
            throw new SPException(SPException::SP_WARNING, _('Formato de XML inválido'), _('No hay categorías para importar'));
        }

        /** @var \DOMElement $category */
        foreach ($this->xmlDOM->getElementsByTagName('Category') as $category) {
            $name = '';
            $description = '';

            foreach ($category->childNodes as $node) {
                if (isset($node->tagName)) {
                    switch ($node->tagName) {
                        case 'name':
                            $name = $node->nodeValue;
                            break;
                        case 'description':
                            $description = $node->nodeValue;
                            break;
                    }
                }
            }

            $this->categories[$category->getAttribute('id')] = $this->addCategory($name, $description);
        }
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function processCustomers()
    {
        if ($this->xmlDOM->getElementsByTagName('Customers')->length === 0) {
            throw new SPException(SPException::SP_WARNING, _('Formato de XML inválido'), _('No hay clientes para importar'));
        }

        /** @var \DOMElement $customer */
        foreach ($this->xmlDOM->getElementsByTagName('Customer') as $customer) {
            $name = '';
            $description = '';

            foreach ($customer->childNodes as $node) {
                if (isset($node->tagName)) {
                    switch ($node->tagName) {
                        case 'name':
                            $name = $node->nodeValue;
                            break;
                        case 'description':
                            $description = $node->nodeValue;
                            break;
                    }
                }
            }

            $this->customers[$customer->getAttribute('id')] = $this->addCustomer($name, $description);
        }
    }

    /**
     * Obtener los datos de las entradas de sysPass y crearlas.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function processAccounts()
    {
        if ($this->xmlDOM->getElementsByTagName('Accounts')->length === 0) {
            throw new SPException(SPException::SP_WARNING, _('Formato de XML inválido'), _('No hay cuentas para importar'));
        }

        $AccountData = new AccountExtData();

        foreach ($this->xmlDOM->getElementsByTagName('Account') as $account) {
            $AccountDataClone = clone $AccountData;

            foreach ($account->childNodes as $node) {
                if (isset($node->tagName)) {
                    switch ($node->tagName) {
                        case 'name';
                            $AccountDataClone->setAccountName($node->nodeValue);
                            break;
                        case 'login';
                            $AccountDataClone->setAccountLogin($node->nodeValue);
                            break;
                        case 'categoryId';
                            $AccountDataClone->setAccountCategoryId($this->categories[(int)$node->nodeValue]);
                            break;
                        case 'customerId';
                            $AccountDataClone->setAccountCustomerId($this->customers[(int)$node->nodeValue]);
                            break;
                        case 'url';
                            $AccountDataClone->setAccountUrl($node->nodeValue);
                            break;
                        case 'pass';
                            $AccountDataClone->setAccountPass(base64_decode($node->nodeValue));
                            break;
                        case 'passiv';
                            $AccountDataClone->setAccountIV(base64_decode($node->nodeValue));
                            break;
                        case 'notes';
                            $AccountDataClone->setAccountNotes($node->nodeValue);
                            break;
                    }
                }
            }

            $this->addAccount($AccountDataClone);
        }
    }
}