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

use SP\Bootstrap;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Dic\DicInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Session\Session;
use SP\Core\SessionFactory;
use SP\DataModel\ItemSearchData;
use SP\Storage\DbWrapper;
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
            'SELECT A.userId,
            A.userEditId,
            A.name,
            C.name AS clientName 
            FROM Account A 
            LEFT JOIN Client C ON A.clientId = C.id 
            WHERE A.id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data);

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
            'SELECT U.name
            FROM AccountToUser AU
            INNER JOIN User U ON AU.userId = U.id
            WHERE AU.accountId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        $queryRes = DbWrapper::getResultsArray($Data);

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
            FROM Account';

        $Data = new QueryData();
        $Data->setQuery($query);

        try {
            return DbWrapper::getResultsArray($Data);
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
            'SELECT account_name FROM Account WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        $queryRes = DbWrapper::getResults($Data);

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
            'SELECT account_name FROM Account WHERE account_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data);
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
        $Data->setSelect('account_id, account_name, name');
        $Data->setFrom('accounts LEFT JOIN customers ON account_customerId = id');
        $Data->setOrder('account_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('account_name LIKE ? OR name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Devolver las cuentas enlazadas
     *
     * @param         $accountId
     * @param Session $session
     * @return array
     */
    public static function getLinkedAccounts($accountId, Session $session)
    {
        if ($accountId === 0) {
            return [];
        }

        $Data = new QueryData();

        $queryWhere = self::getAccountFilterUser($Data, $session);

        $queryWhere[] = 'A.parentId = ?';
        $Data->addParam($accountId);

        $query = /** @lang SQL */
            'SELECT A.id, A.name, C.name AS clientName 
            FROM Account A
            INNER JOIN Client C ON Account.clientId = C.id 
            WHERE ' . implode(' AND ', $queryWhere) . ' ORDER  BY name';

        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     *
     * @param QueryData $Data
     * @param Session   $session
     * @param bool      $useGlobalSearch
     * @return array
     */
    public static function getAccountFilterUser(QueryData $Data, Session $session, $useGlobalSearch = false)
    {
        $configData = $session->getConfig();
        $userData = $session->getUserData();

        if (!$userData->getIsAdminApp()
            && !$userData->getIsAdminAcc()
            && !($useGlobalSearch && $session->getUserProfile()->isAccGlobalSearch() && $configData->isGlobalSearch())
        ) {
            // Filtro usuario y grupo
            $filterUser[] = 'userId = ?';
            $Data->addParam($userData->getId());

            $filterUser[] = 'userGroupId = ?';
            $Data->addParam($userData->getUserGroupId());

            // Filtro de cuenta en usuarios y grupos secundarios
            $filterUser[] = /** @lang SQL */
                'A.id IN (SELECT accountId AS accountId FROM AccountToUser WHERE accountId = A.id AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = A.id AND userGroupId = ?)';
            $Data->addParam($userData->getId());
            $Data->addParam($userData->getUserGroupId());

            // Filtro de grupo principal de cuenta en grupos que incluyen al usuario
            $filterUser[] = /** @lang SQL */
                'A.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = Account.userGroupId AND userId = ?)';
            $Data->addParam($userData->getId());

            if ($configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filterUser[] = /** @lang SQL */
                    'A.id = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = A.id AND uug.userId = ? LIMIT 1)';
                $Data->addParam($userData->getId());
            }

            $queryWhere[] = '(' . implode(' OR ', $filterUser) . ')';
        }

        $queryWhere[] = '(isPrivate = 0 OR (isPrivate = 1 AND userId = ?))';
        $Data->addParam($userData->getId());
        $queryWhere[] = '(isPrivateGroup = 0 OR (isPrivateGroup = 1 AND userGroupId = ?))';
        $Data->addParam($userData->getUserGroupId());

        return $queryWhere;
    }

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     *
     * @param QueryData $Data
     * @param Session   $session
     * @param bool      $useGlobalSearch
     * @return array
     */
    public static function getAccountHistoryFilterUser(QueryData $Data, Session $session, $useGlobalSearch = false)
    {
        $configData = $session->getConfig();
        $userData = $session->getUserData();

        if (!$userData->getIsAdminApp()
            && !$userData->getIsAdminAcc()
            && !($useGlobalSearch && $session->getUserProfile()->isAccGlobalSearch() && $configData->isGlobalSearch())
        ) {
            // Filtro usuario y grupo
            $filterUser[] = 'AH.userId = ?';
            $Data->addParam($userData->getId());

            $filterUser[] = 'AH.userGroupId = ?';
            $Data->addParam($userData->getUserGroupId());

            // Filtro de cuenta en usuarios y grupos secundarios
            $filterUser[] = /** @lang SQL */
                'AH.accountId IN (SELECT accountId FROM AccountToUser WHERE accountId = AH.accountId AND userId = ? UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = account_id AND AH.accountId = ?)';
            $Data->addParam($userData->getId());
            $Data->addParam($userData->getUserGroupId());

            // Filtro de grupo principal de cuenta en grupos que incluyen al usuario
            $filterUser[] = /** @lang SQL */
                'AH.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = AH.userGroupId AND userId = ?)';
            $Data->addParam($userData->getId());

            if ($configData->isAccountFullGroupAccess()) {
                // Filtro de grupos secundarios en grupos que incluyen al usuario
                $filterUser[] = /** @lang SQL */
                    'AH.accountId = (SELECT accountId FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = AH.accountId AND uug.userId = ? LIMIT 1)';
                $Data->addParam($userData->getId());
            }

            $queryWhere[] = '(' . implode(' OR ', $filterUser) . ')';
        }

        $queryWhere[] = '(AH.isPrivate = 0 OR (AH.isPrivate = 1 AND AH.userId = ?))';
        $Data->addParam($userData->getId());
        $queryWhere[] = '(AH.isPrivateGroup = 0 OR (AH.isPrivateGroup = 1 AND AH.userGroupId = ?))';
        $Data->addParam($userData->getUserGroupId());

        return $queryWhere;
    }

    /**
     * Obtiene los datos de las cuentas visibles por el usuario
     *
     * @param Session $session
     * @param int     $accountId Cuenta actual
     * @return array
     */
    public static function getAccountsForUser(Session $session, $accountId = null)
    {
        $Data = new QueryData();

        $queryWhere = self::getAccountFilterUser($Data, $session);

        if (null !== $accountId) {
            $queryWhere[] = 'A.id <> ? AND (A.parentId = 0 OR A.parentId IS NULL)';
            $Data->addParam($accountId);
        }

        $query = /** @lang SQL */
            'SELECT A.id, A.name, C.name AS clientName 
            FROM Account A
            LEFT JOIN Client C ON A.clientId = C.id 
            WHERE ' . implode(' AND ', $queryWhere) . ' ORDER BY name';

        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Devolver el número de cuentas a procesar
     *
     * @return int
     */
    public static function getTotalNumAccounts()
    {
        $query = /** @lang SQL */
            'SELECT SUM(n) AS num FROM (SELECT COUNT(*) AS n FROM Account UNION SELECT COUNT(*) AS n FROM AccountHistory) a';

        $Data = new QueryData();
        $Data->setQuery($query);

        return (int)DbWrapper::getResults($Data)->num;
    }
}