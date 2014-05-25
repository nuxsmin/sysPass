<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de importar cuentas desde KeePass
 */
class SP_KeePassImport
{

    /**
     * @brief Iniciar la importación desde KeePass
     * @param object $xml
     * @return bool
     */
    public static function addKeepassAccounts($xml)
    {
        self::getGroups($xml->Root->Group);
    }

    /**
     * @brief Obtener los datos de las entradas de KeePass
     * @param object $entries con el objeto XML con las entradas
     * @param string $groupName con nombre del grupo a procesar
     * @throws ImportException
     * @return bool
     */
    private static function getEntryData($entries, $groupName)
    {
        foreach ( $entries as $entry ){
            foreach ( $entry->String as $account ){
                switch ($account->Key){
                    case 'Notes':
                        $notes = $account->Value;
                        break;
                    case 'Password':
                        $password = $account->Value;
                        break;
                    case 'Title':
                        $name = $account->Value;
                        break;
                    case 'url':
                        $url = $account->Value;
                        break;
                    case 'UserName':
                        $username = $account->Value;
                        break;
                }
            }

            $accountData = array($name,'KeePass',$groupName,$url,$username,$password,$notes);
            SP_Import::addAccountData($accountData);
        }
    }

    /**
     * @brief Obtener los grupos y procesar lan entradas de KeePass
     * @param object $xml con objeto XML del archivo de KeePass
     * @throws ImportException
     * @return bool
     */
    private static function getGroups($xml)
    {
        foreach($xml as $node){
            if ( $node->Group ){
                foreach ( $node->Group as $group ){
                    $groupName = $group->Name;
                    // Analizar grupo
                    if ( $node->Group->Entry ){
                        // Obtener entradas
                        self::getEntryData($group->Entry,$groupName);
                    }

                    if ( $group->Group ){
                        // Analizar subgrupo
                        self::getGroups($group);
                    }
                }
            }

            if ( $node->Entry ){
                $groupName = $node->Name;
                // Obtener entradas
                self::getEntryData($node->Entry,$groupName);
            }
        }
    }
} 