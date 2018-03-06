<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Storage;


use RuntimeException;
use SP\Core\Exceptions\SPException;

/**
 * Class DBUtil con utilidades de la BD
 *
 * @package SP\Storage
 */
class DBUtil
{
    /**
     * @var array Tablas de la BBDD
     */
    public static $tables = [
        'Client',
        'Category',
        'Tag',
        'UserGroup',
        'UserProfile',
        'User',
        'Account',
        'AccountToFavorite',
        'AccountFile',
        'AccountToUserGroup',
        'AccountHistory',
        'AccountToTag',
        'AccountToUser',
        'AuthToken',
        'Config',
        'Action',
        'CustomFieldType',
        'CustomFieldDefinition',
        'CustomFieldData',
        'EventLog',
        'PublicLink',
        'UserPassRecover',
        'UserToUserGroup',
        'Plugin',
        'Notification',
        'account_data_v',
        'account_search_v'
    ];

    /**
     * Escapar una cadena de texto con funciones de mysqli.
     *
     * @param string             $str string con la cadena a escapar
     * @param DBStorageInterface $DBStorage
     * @return string con la cadena escapada
     */
    public static function escape($str, DBStorageInterface $DBStorage)
    {
        try {
            return $DBStorage->getConnection()->quote(trim($str));
        } catch (SPException $e) {
            debugLog($e->getMessage());
            debugLog($e->getHint());
        }

        return $str;
    }

    /**
     * Obtener la información del servidor de base de datos
     *
     * @param DBStorageInterface $DBStorage
     * @return array
     * @throws SPException
     */
    public static function getDBinfo(DBStorageInterface $DBStorage)
    {
        $dbinfo = [];

        try {
            $db = $DBStorage->getConnection();

            $attributes = [
                'SERVER_VERSION',
                'CLIENT_VERSION',
                'SERVER_INFO',
                'CONNECTION_STATUS',
            ];

            foreach ($attributes as $val) {
                $dbinfo[$val] = $db->getAttribute(constant('PDO::ATTR_' . $val));
            }
        } catch (SPException $e) {
            debugLog($e->getMessage());
            debugLog($e->getHint());
            debugLog($e->getCode());

            throw $e;
        } catch (\Exception $e) {
            debugLog($e->getMessage());
        }

        return $dbinfo;
    }

    /**
     * Comprobar que la base de datos existe.
     *
     * @param DBStorageInterface $DBStorage
     * @param string             $dbName
     * @return bool
     * @throws SPException
     */
    public static function checkDatabaseExist(DBStorageInterface $DBStorage, $dbName)
    {
        try {
            $query = /** @lang SQL */
                'SELECT COUNT(*) 
                FROM information_schema.tables
                WHERE table_schema = \'' . $dbName . '\'
                AND `table_name` IN (\'Client\', \'Category\', \'Account\', \'User\', \'Config\', \'EventLog\')';

            return (int)$DBStorage->getConnection()->query($query)->fetchColumn() === 6;
        } catch (SPException $e) {
            debugLog($e->getMessage());
            debugLog($e->getHint());

            throw $e;
        } catch (\Exception $e) {
            debugLog($e->getMessage());
            debugLog($e->getCode());

            throw new RuntimeException(__u('Error en la verificación de la base de datos'));
        }
    }
}