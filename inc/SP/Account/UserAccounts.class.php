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

namespace SP\Account;

use SP\DataModel\UserData;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

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
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
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
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function deleteUsersForAccount($accountId, array $usersId = [])
    {
        $Data = new QueryData();

        $numUsers = count($usersId);

        // Excluimos los usuarios actuales
        if ($numUsers > 0) {
            $params = implode(',', array_fill(0, $numUsers, '?'));

            $query = /** @lang SQL */
                'DELETE FROM accUsers WHERE accuser_accountId = ? AND accuser_userId NOT IN (' . $params . ')';

            $Data->setParams(array_merge((array)$accountId, $usersId));
        } else {
            $query = /** @lang SQL */
                'DELETE FROM accUsers WHERE accuser_accountId = ?';
                
            $Data->addParam($accountId);
        }

        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al eliminar usuarios asociados a la cuenta', false));

        return DB::getQuery($Data);
    }

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $usersId   con los usuarios de la cuenta
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function addUsersForAccount($accountId, array $usersId = [])
    {
        $numUsers = count($usersId);

        if ($numUsers === 0) {
            return true;
        }

        // Obtenemos los usuarios actuales
        $usersExcluded = self::getUsersForAccount($accountId);

        // Excluimos los usuarios actuales
        if (count($usersExcluded) > 0) {
            $usersId = array_diff($usersId, $usersExcluded);
        }

        $params = array_fill(0, count($usersId), '(?,?)');

        if (count($params) === 0) {
            return true;
        }

        $query = /** @lang SQL */
            'INSERT INTO accUsers (accuser_accountId, accuser_userId) VALUES ' . implode(',', $params);

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al actualizar los usuarios de la cuenta', false));

        foreach ($usersId as $userId) {
            $Data->addParam($accountId);
            $Data->addParam($userId);
        }

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
        $query = /** @lang SQL */
            'SELECT accuser_userId FROM accUsers WHERE accuser_accountId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        $users = [];

        foreach (DB::getResultsArray($Data) as $user) {
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
        $query = /** @lang SQL */
            'SELECT user_id,
            user_login,
            user_name
            FROM accUsers
            JOIN usrData ON user_Id = accuser_userId
            WHERE accuser_accountId = ?
            ORDER BY user_login';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);
        $Data->addParam($accountId);

        return DB::getResultsArray($Data);
    }
}