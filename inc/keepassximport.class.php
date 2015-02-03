<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de importar cuentas desde KeePassX
 */
class SP_KeePassXImport
{

    /**
     * Iniciar la importación desde KeePass.
     *
     * @param object $xml
     * @return none
     */
    public static function addKeepassXAccounts($xml)
    {
        self::getGroups($xml);
    }

    /**
     * Obtener los datos de las entradas de KeePass.
     *
     * @param object $entries con el objeto XML con las entradas
     * @param string $groupName con nombre del grupo a procesar
     * @throws ImportException
     * @return none
     */
    private static function getEntryData($entries, $groupName)
    {
        foreach ( $entries as $entry ){
            $notes = (isset($entry->comment)) ? (string) $entry->comment : '';
            $password = (isset($entry->password)) ? (string) $entry->password : '';
            $name = (isset($entry->title)) ? (string) $entry->title : '';
            $url = (isset($entry->url)) ? (string) $entry->url : '' ;
            $username = (isset($entry->username)) ? (string) $entry->username : '';

            $accountData = array($name,'KeePassX',$groupName,$url,$username,$password,$notes);
            SP_Import::addAccountData($accountData);
        }
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @param object $xml con objeto XML del archivo de KeePass
     * @throws ImportException
     * @return none
     */
    private static function getGroups($xml)
    {
        foreach($xml as $node){
            if ( $node->group ){
                foreach ( $node->group as $group ){
                    $groupName = $group->title;
                    // Analizar grupo
                    if ( $node->group->entry ){
                        // Obtener entradas
                        self::getEntryData($group->entry,$groupName);
                    }

                    if ( $group->group ){
                        // Analizar subgrupo
                        self::getGroups($group);
                    }
                }
            }

            if ( $node->entry ){
                $groupName = $node->title;
                // Obtener entradas
                self::getEntryData($node->entry,$groupName);
            }
        }
    }
} 