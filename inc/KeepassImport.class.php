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
class KeepassImport extends XmlImportBase
{
    /**
     * Iniciar la importación desde KeePass
     */
    public function doImport()
    {
        $this->setCustomerName('KeePass');
        $this->setCustomerId($this->addCustomer());

        $this->processCategories($this->_xml->Root->Group);
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @param \SimpleXMLElement $xml El objeto XML del archivo de KeePass
     */
    protected function processCategories(\SimpleXMLElement $xml)
    {
        foreach ($xml as $node) {
            if ($node->Group) {
                foreach ($node->Group as $group) {
                    // Analizar grupo
                    if ($node->Group->Entry) {
                        // Crear la categoría
                        $this->setCategoryName($group->Name);
                        $this->setCategoryId($this->addCategory());

                        // Crear cuentas
                        $this->processAccounts($group->Entry);
                    }

                    if ($group->Group) {
                        // Analizar subgrupo
                        $this->processCategories($group);
                    }
                }
            }

            if ($node->Entry) {
                // Crear la categoría
                $this->setCategoryName($node->Name);
                $this->setCategoryId($this->addCategory());

                // Crear cuentas
                $this->processAccounts($node->Entry);
            }
        }
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param \SimpleXMLElement $entries El objeto XML con las entradas
     */
    protected function processAccounts(\SimpleXMLElement $entries)
    {
        foreach ($entries as $entry) {
            foreach ($entry->String as $account) {
                $value = (isset($account->Value)) ? (string)$account->Value : '';
                switch ($account->Key) {
                    case 'Notes':
                        $this->setAccountNotes($value);
                        break;
                    case 'Password':
                        $passData = Crypt::encryptData($value);

                        $this->setAccountPass($passData['pass']);
                        $this->setAccountPassIV($passData['IV']);
                        break;
                    case 'Title':
                        $this->setAccountName($value);
                        break;
                    case 'URL':
                        $this->setAccountUrl($value);
                        break;
                    case 'UserName':
                        $this->setAccountLogin($value);
                        break;
                }
            }

            $this->addAccount();
        }
    }
}