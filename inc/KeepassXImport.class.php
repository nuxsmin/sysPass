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
 * Esta clase es la encargada de importar cuentas desde KeePassX
 */
class KeepassXImport extends XmlImportBase
{
    /**
     * Iniciar la importación desde KeePassX.
     *
     * @throws SPException
     * @return bool
     */
    public function doImport()
    {
        $this->setCustomerName('KeePassX');
        $this->setCustomerId($this->addCustomer());

        self::processCategories($this->_xml);
    }


    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @param \SimpleXMLElement $xml con objeto XML del archivo de KeePass
     */
    protected function processCategories(\SimpleXMLElement $xml)
    {
        foreach ($xml as $node) {
            if ($node->group) {
                foreach ($node->group as $group) {
                    // Analizar grupo
                    if ($node->group->entry) {
                        // Crear la categoría
                        $this->setCategoryName($group->title);
                        $this->setCategoryId($this->addCategory());

                        // Crear cuentas
                        $this->processAccounts($group->entry);
                    }

                    if ($group->group) {
                        // Analizar subgrupo
                        $this->processCategories($group);
                    }
                }
            }

            if ($node->entry) {
                // Crear la categoría
                $this->setCategoryName($node->title);
                $this->setCategoryId($this->addCategory());

                // Crear cuentas
                $this->processAccounts($node->entry);
            }
        }
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param \SimpleXMLElement $entries El objeto XML con las entradas
     * @param string $groupName con nombre del grupo a procesar
     */
    protected function processAccounts(\SimpleXMLElement $entries, $groupName)
    {
        foreach ($entries as $entry) {
            $notes = (isset($entry->comment)) ? (string)$entry->comment : '';
            $password = (isset($entry->password)) ? (string)$entry->password : '';
            $name = (isset($entry->title)) ? (string)$entry->title : '';
            $url = (isset($entry->url)) ? (string)$entry->url : '';
            $username = (isset($entry->username)) ? (string)$entry->username : '';

            $passData = Crypt::encryptData($password);

            $this->setAccountPass($passData['data']);
            $this->setAccountPassIV($passData['iv']);
            $this->setAccountNotes($notes);
            $this->setAccountName($name);
            $this->setAccountUrl($url);
            $this->setAccountLogin($username);

            $this->addAccount();
        }
    }
}