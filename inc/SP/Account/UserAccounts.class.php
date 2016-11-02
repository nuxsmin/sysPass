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

namespace SP\Account;

use SP\DataModel\UserData;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class UserAccounts para la gestión de usuarios en las cuentas
 *
 * @package SP
 */
class UserAccounts
{
    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $usersId   con los usuarios de la cuenta
     * @return bool
     */
    public static function updateUsersForAccount($accountId, $usersId)
    {
        if (self::deleteUsersForAccount($accountId, $usersId)) {
            return self::addUsersForAccount($accountId, $usersId);
        }

        return false;
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $usersId   opcional con los grupos de la cuenta
     * @return bool
     */
    public static function deleteUsersForAccount($accountId, $usersId = null)
    {
        $queryExcluded = '';

        // Excluimos los usuarios actuales
        if ($usersId !== null && count($usersId) > 0) {
            array_map('intval', $usersId);
            $queryExcluded = 'AND accuser_userId NOT IN (' . implode(',', $usersId) . ')';
        }

        $query = 'DELETE FROM accUsers WHERE accuser_accountId = :id ' . $queryExcluded;

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        return DB::getQuery($Data);
    }

    /**
     * Crear asociación de grupos con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $usersId   con los grupos de la cuenta
     * @return bool
     */
    public static function addUsersForAccount($accountId, $usersId)
    {
        if (!is_array($usersId)) {
            return true;
        }

        $values = '';

        // Obtenemos los grupos actuales
        $usersExcluded = self::getUsersForAccount($accountId);

        foreach ($usersId as $userId) {
            // Excluimos los usuarios actuales
            if (isset($usersExcluded) && is_array($usersExcluded) && in_array($userId, $usersExcluded)) {
                continue;
            }

            $values[] = '(' . (int)$accountId . ',' . (int)$userId . ')';
        }

        if (!is_array($values)) {
            return true;
        }

        $query = 'INSERT INTO accUsers (accuser_accountId, accuser_userId) VALUES ' . implode(',', $values);

        $Data = new QueryData();
        $Data->setQuery($query);

        return DB::getQuery($Data);
    }

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param int $accountId con el id de la cuenta
     * @return array con los id de usuarios de la cuenta
     */
    public static function getUsersForAccount($accountId)
    {
        $query = 'SELECT accuser_userId FROM accUsers WHERE accuser_accountId = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        DB::setReturnArray();

        $users = [];

        foreach (DB::getResults($Data) as $user) {
            $users[] = (int)$user->accuser_userId;
        }

        return $users;
    }

    /**
     * Obtiene el listado con el nombre de los usuarios de una cuenta.
     *
     * @param int $accountId con el id de la cuenta
     * @return UserData[]
     */
    public static function getUsersInfoForAccount($accountId)
    {
        $query = 'SELECT user_id,
            user_login,
            user_name
            FROM accUsers
            JOIN usrData ON user_Id = accuser_userId
            WHERE accuser_accountId = :id
            ORDER BY user_login';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\UserData');
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        DB::setReturnArray();

        return DB::getResults($Data);
    }
}