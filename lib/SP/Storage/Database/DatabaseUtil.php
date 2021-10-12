<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Storage\Database;

use Exception;

/**
 * Class DBUtil con utilidades de la BD
 *
 * @package SP\Storage
 */
final class DatabaseUtil
{
    /**
     * @var array Tablas de la BBDD
     */
    public const TABLES = [
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
        'CustomFieldType',
        'CustomFieldDefinition',
        'CustomFieldData',
        'EventLog',
        'PublicLink',
        'UserPassRecover',
        'UserToUserGroup',
        'Plugin',
        'Track',
        'Notification',
        'account_data_v',
        'account_search_v'
    ];
    private DBStorageInterface $DBStorage;

    /**
     * DatabaseUtil constructor.
     */
    public function __construct(DBStorageInterface $DBStorage)
    {
        $this->DBStorage = $DBStorage;
    }

    /**
     * Comprobar que la base de datos existe.
     */
    public function checkDatabaseTables(string $dbName): bool
    {
        try {
            $tables = implode(',', array_map(
                static function ($value) {
                    return '\'' . $value . '\'';
                },
                self::TABLES
            ));

            $query = /** @lang SQL */
                sprintf('SELECT COUNT(*) 
                FROM information_schema.tables
                WHERE table_schema = \'%s\'
                AND `table_name` IN (%s)', $dbName, $tables);

            $numTables = $this->DBStorage
                ->getConnection()
                ->query($query)
                ->fetchColumn();

            return (int)$numTables === count(self::TABLES);
        } catch (Exception $e) {
            processException($e);
        }

        return false;
    }

    public function checkDatabaseConnection(): bool
    {
        try {
            $this->DBStorage->getConnection();

            return true;
        } catch (Exception $e) {
            processException($e);

            return false;
        }
    }

    /**
     * Obtener la información del servidor de base de datos
     */
    public function getDBinfo(): array
    {
        $dbinfo = [];

        try {
            $db = $this->DBStorage->getConnection();

            $attributes = [
                'SERVER_VERSION',
                'CLIENT_VERSION',
                'SERVER_INFO',
                'CONNECTION_STATUS',
            ];

            foreach ($attributes as $val) {
                $dbinfo[$val] = $db->getAttribute(constant('PDO::ATTR_' . $val));
            }
        } catch (Exception $e) {
            processException($e);

            logger($e->getMessage());
        }

        return $dbinfo;
    }

    /**
     * Escapar una cadena de texto con funciones de mysqli.
     */
    public function escape(string $str): string
    {
        try {
            return $this->DBStorage->getConnection()->quote(trim($str));
        } catch (Exception $e) {
            processException($e);
        }

        return $str;
    }
}