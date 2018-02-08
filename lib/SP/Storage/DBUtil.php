<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Bootstrap;
use SP\Config\ConfigData;
use SP\Core\DiFactory;
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
        'Customer',
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
        'AccountTouser',
        'AuthToken',
        'Config',
        'Action',
        'CustomFieldType',
        'CustomFieldDefinition',
        'customFieldData',
        'EventLog',
        'PublicLink',
        'UserPassRecover',
        'UserToUserGroup',
        'Plugin',
        'Notice',
        'account_data_v',
        'account_search_v'
    ];

    /**
     * Escapar una cadena de texto con funciones de mysqli.
     *
     * @param $str string con la cadena a escapar
     * @return string con la cadena escapada
     */
    public static function escape($str)
    {
        try {
            $db = DiFactory::getDBStorage()->getConnection();

            return $db->quote(trim($str));
        } catch (SPException $e) {
            return $str;
        }
    }

    /**
     * Obtener la información del servidor de base de datos
     *
     * @return array
     */
    public static function getDBinfo()
    {
        $dbinfo = array();

        try {
            $db = DiFactory::getDBStorage()->getConnection();

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
            return $dbinfo;
        }

        return $dbinfo;
    }

    /**
     * Comprobar que la base de datos existe.
     *
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public static function checkDatabaseExist()
    {
        $dic = Bootstrap::getContainer();
        /** @var Database $Db */
        $Db = $dic->get(Database::class);
        /** @var ConfigData $ConfigData */
        $ConfigData = $dic->get(ConfigData::class);

        try {
            $query = /** @lang SQL */
                'SELECT COUNT(*) 
                FROM information_schema.tables
                WHERE table_schema = \'' . $ConfigData->getDbName() . '\'
                AND table_name IN (\'Client\', \'Category\', \'Account\', \'User\', \'Config\', \'EventLog\')';

            return (int)$Db->getDbHandler()->getConnection()->query($query)->fetchColumn() === 6;
        } catch (\Exception $e) {
            debugLog($e->getMessage());
            debugLog($e->getCode());

            throw new RuntimeException(__u('Error en la verificación de la base de datos'));
        }
    }
}