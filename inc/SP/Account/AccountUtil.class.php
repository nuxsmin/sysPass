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

use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\DataModel\ItemSearchData;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

/**
 * Class AccountUtil con utilidades para la gestión de cuentas
 *
 * @package SP
 */
class AccountUtil
{
    /**
     * Obtener los datos de usuario y modificador de una cuenta.
     *
     * @param int $id con el Id de la cuenta
     * @return false|object con el id de usuario y modificador.
     */
    public static function getAccountRequestData($id)
    {
        $query = /** @lang SQL */
            'SELECT account_userId,
            account_userEditId,
            account_name,
            customer_name 
            FROM accounts 
            LEFT JOIN customers ON account_customerId = customer_id 
            WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes;
    }

    /**
     * Obtiene el listado con el nombre de los usuaios de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con los nombres de los usuarios ordenados
     */
    public static function getAccountUsersName($accountId)
    {
        $query = /** @lang SQL */
            'SELECT user_name 
            FROM accUsers 
            JOIN usrData ON accuser_userId = user_id 
            WHERE accuser_accountId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        $queryRes = DB::getResultsArray($Data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $users) {
            $usersName[] = $users->user_name;
        }

        sort($usersName, SORT_STRING);

        return $usersName;
    }

    /**
     * Obtener los datos de todas las cuentas
     *
     * @return array
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function getAccountsData()
    {
        $query = /** @lang SQL */
            'SELECT account_id,
            account_name,
            account_categoryId,
            account_customerId,
            account_login,
            account_url,
            account_pass,
            account_key,
            account_notes 
            FROM accounts';

        $Data = new QueryData();
        $Data->setQuery($query);

        try {
            return DB::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, __('No se pudieron obtener los datos de las cuentas', false));
        }
    }

    /**
     * Devolver el nombre de la cuenta a partir del Id
     *
     * @param int $accountId El Id de la cuenta
     * @return string|bool
     */
    public static function getAccountNameById($accountId)
    {
        $query = /** @lang SQL */
            'SELECT account_name FROM accounts WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        $queryRes = DB::getResults($Data);

        return is_object($queryRes) ? $queryRes->account_name : false;
    }

    /**
     * Devolver el nombre de la cuenta a partir del Id
     *
     * @param array $ids Id de la cuenta
     * @return array
     * @internal param int $accountId El Id de la cuenta
     */
    public static function getAccountNameByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT account_name FROM accounts WHERE account_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DB::getResultsArray($Data);
    }

    /**
     *  Obtener los datos de todas las cuentas y el cliente mediante una búsqueda
     *
     * @param ItemSearchData $SearchData
     * @return array|bool
     */
    public static function getAccountsMgmtSearch(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('account_id, account_name, customer_name');
        $Data->setFrom('accounts LEFT JOIN customers ON account_customerId = customer_id');
        $Data->setOrder('account_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('account_name LIKE ? OR customer_name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DB::setFullRowCount();

        $queryRes = DB::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Devolver las cuentas enlazadas
     *
     * @param $accountId
     * @return array
     */
    public static function getLinkedAccounts($accountId)
    {
        if ($accountId === 0) {
            return [];
        }

        $Data = new QueryData();

        $queryWhere = self::getAccountFilterUser($Data);

        $queryWhere[] = 'account_parentId = ?';
        $Data->addParam($accountId);

        $query = /** @lang SQL */
            'SELECT account_id, account_name, customer_name 
            FROM accounts 
            LEFT JOIN customers ON customer_id = account_customerId 
            WHERE ' . implode(' AND ', $queryWhere) . ' ORDER  BY customer_name';

        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     *
     * @param QueryData $Data
     * @param bool $useGlobalSearch
     * @return array
     */
    public static function getAccountFilterUser(QueryData $Data, $useGlobalSearch = false)
    {
        if (!Session::getUserData()->isUserIsAdminApp()
            && !Session::getUserData()->isUserIsAdminAcc()
            && !($useGlobalSearch && Session::getUserProfile()->isAccGlobalSearch() && Config::getConfig()->isGlobalSearch())
        ) {
            // Filtro usuario y grupo
            $filterUser[] = 'account_userId = ?';
            $Data->addParam(Session::getUserData()->getUserId());
            $filterUser[] = 'account_userGroupId = ?';
            $Data->addParam(Session::getUserData()->getUserGroupId());

            // Filtro de cuenta en usuarios y grupos secundarios
            $filterUser[] = /** @lang SQL */
                'account_id IN (SELECT accuser_accountId AS accountId FROM accUsers WHERE accuser_accountId = account_id AND accuser_userId = ? UNION ALL SELECT accgroup_accountId AS accountId FROM accGroups WHERE accgroup_accountId = account_id AND accgroup_groupId = ?)';
            $Data->addParam(Session::getUserData()->getUserId());
            $Data->addParam(Session::getUserData()->getUserGroupId());

            // Filtro de grupo principal de cuenta en grupos que incluyen al usuario
            $filterUser[] = /** @lang SQL */
                'account_userGroupId IN (SELECT usertogroup_groupId FROM usrToGroups WHERE usertogroup_groupId = account_userGroupId AND usertogroup_userId = ?)';
            $Data->addParam(Session::getUserData()->getUserId());

            if (Config::getConfig()->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filterUser[] = /** @lang SQL */
                    'account_id = (SELECT accgroup_accountId AS accountId FROM accGroups INNER JOIN usrToGroups ON usertogroup_groupId = accgroup_groupId WHERE accgroup_accountId = account_id AND usertogroup_userId = ? LIMIT 1)';
                $Data->addParam(Session::getUserData()->getUserId());
            }

            $queryWhere[] = '(' . implode(' OR ', $filterUser) . ')';
        }

        $queryWhere[] = '(account_isPrivate = 0 OR (account_isPrivate = 1 AND account_userId = ?))';
        $Data->addParam(Session::getUserData()->getUserId());
        $queryWhere[] = '(account_isPrivateGroup = 0 OR (account_isPrivateGroup = 1 AND account_userGroupId = ?))';
        $Data->addParam(Session::getUserData()->getUserGroupId());

        return $queryWhere;
    }

    /**
     * Obtiene los datos de las cuentas visibles por el usuario
     *
     * @param int $accountId Cuenta actual
     * @return array
     */
    public static function getAccountsForUser($accountId = 0)
    {
        $Data = new QueryData();

        $queryWhere = self::getAccountFilterUser($Data);

        if (!empty($accountId)) {
            $queryWhere[] = 'account_id <> ? AND (account_parentId = 0 OR account_parentId IS NULL)';
            $Data->addParam($accountId);
        }

        $query = /** @lang SQL */
            'SELECT account_id, account_name, customer_name 
            FROM accounts 
            LEFT JOIN customers ON customer_id = account_customerId 
            WHERE ' . implode(' AND ', $queryWhere) . ' ORDER BY customer_name';

        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }

    /**
     * Devolver el número de cuentas a procesar
     *
     * @return int
     */
    public static function getTotalNumAccounts()
    {
        $query = /** @lang SQL */
            'SELECT SUM(n) AS num FROM (SELECT COUNT(*) AS n FROM accounts UNION SELECT COUNT(*) AS n FROM accHistory) a';

        $Data = new QueryData();
        $Data->setQuery($query);

        return (int)DB::getResults($Data)->num;
    }
}