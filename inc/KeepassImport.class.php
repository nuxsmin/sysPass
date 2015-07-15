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
        $this->getGroups($this->_xml->Root->Group);
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param \SimpleXMLElement $entries  El objeto XML con las entradas
     * @param string $groupName con nombre del grupo a procesar
     */
    protected function getEntryData(\SimpleXMLElement $entries, $groupName)
    {
        foreach ($entries as $entry) {
            foreach ($entry->String as $account) {
                $value = (isset($account->Value)) ? (string)$account->Value : '';
                switch ($account->Key) {
                    case 'Notes':
                        $notes = $value;
                        break;
                    case 'Password':
                        $password = $value;
                        break;
                    case 'Title':
                        $name = $value;
                        break;
                    case 'URL':
                        $url = $value;
                        break;
                    case 'UserName':
                        $username = $value;
                        break;
                }
            }

            $accountData = array($name, 'KeePass', $groupName, $url, $username, $password, $notes);
            Import::addAccountData($accountData);
        }
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @param \SimpleXMLElement $xml El objeto XML del archivo de KeePass
     */
    protected function getGroups(\SimpleXMLElement $xml)
    {
        foreach ($xml as $node) {
            if ($node->Group) {
                foreach ($node->Group as $group) {
                    $groupName = $group->Name;
                    // Analizar grupo
                    if ($node->Group->Entry) {
                        // Obtener entradas
                        $this->getEntryData($group->Entry, $groupName);
                    }

                    if ($group->Group) {
                        // Analizar subgrupo
                        $this->getGroups($group);
                    }
                }
            }

            if ($node->Entry) {
                $groupName = $node->Name;
                // Obtener entradas
                $this->getEntryData($node->Entry, $groupName);
            }
        }
    }

    /**
     * Obtener los datos de las entradas.
     */
    protected function getAccountData()
    {
        // TODO: Implement getAccountData() method.
    }

    /**
     * Añadir una cuenta en sysPass desde XML
     *
     * @return mixed
     */
    protected function addAccount()
    {
        // TODO: Implement addAccount() method.
    }
}