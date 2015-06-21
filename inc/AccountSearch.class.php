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
 * Class AccountSearch para la gestión de búsquedas de cuentas
 */
class AccountSearch
{
    /**
     * @var int El número de registros de la última consulta
     */
    public static $queryNumRows;

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param array $searchFilter Filtros de búsqueda
     * @return bool Resultado de la consulta
     */
    public static function getAccounts($searchFilter)
    {
        $isAdmin = ($_SESSION['uisadminapp'] || $_SESSION['uisadminacc']);
        $globalSearch = ($searchFilter['globalSearch'] === 1 && Config::getValue('globalsearch', 0));

        $arrFilterCommon = array();
        $arrFilterSelect = array();
        $arrFilterUser = array();
        $arrQueryWhere = array();

        switch ($searchFilter['keyId']) {
            case 1:
                $orderKey = 'account_name';
                break;
            case 2:
                $orderKey = 'category_name';
                break;
            case 3:
                $orderKey = 'account_login';
                break;
            case 4:
                $orderKey = 'account_url';
                break;
            case 5:
                $orderKey = 'customer_name';
                break;
            default :
                $orderKey = 'customer_name, account_name';
                break;
        }

        $querySelect = 'SELECT DISTINCT '
            . 'account_id,'
            . 'account_customerId,'
            . 'category_name,'
            . 'account_name,'
            . 'account_login,'
            . 'account_url,'
            . 'account_notes,'
            . 'account_userId,'
            . 'account_userGroupId,'
            . 'BIN(account_otherUserEdit) AS account_otherUserEdit,'
            . 'BIN(account_otherGroupEdit) AS account_otherGroupEdit,'
            . 'usergroup_name,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN categories ON account_categoryId = category_id '
            . 'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id '
            . 'LEFT JOIN customers ON customer_id = account_customerId '
            . 'LEFT JOIN accUsers ON accuser_accountId = account_id '
            . 'LEFT JOIN accGroups ON accgroup_accountId = account_id';

        if ($searchFilter['txtSearch']) {
            $arrFilterCommon[] = 'account_name LIKE :name';
            $arrFilterCommon[] = 'account_login LIKE :login';
            $arrFilterCommon[] = 'account_url LIKE :url';
            $arrFilterCommon[] = 'account_notes LIKE :notes';

            $data['name'] = '%' . $searchFilter['txtSearch'] . '%';
            $data['login'] = '%' . $searchFilter['txtSearch'] . '%';
            $data['url'] = '%' . $searchFilter['txtSearch'] . '%';
            $data['notes'] = '%' . $searchFilter['txtSearch'] . '%';
        }

        if ($searchFilter['categoryId'] != 0) {
            $arrFilterSelect[] = 'category_id = :categoryId';

            $data['categoryId'] = $searchFilter['categoryId'];
        }
        if ($searchFilter['customerId'] != 0) {
            $arrFilterSelect[] = 'account_customerId = :customerId';

            $data['customerId'] = $searchFilter['customerId'];
        }

        if (count($arrFilterCommon) > 0) {
            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterCommon) . ')';
        }

        if (count($arrFilterSelect) > 0) {
            $arrQueryWhere[] = '(' . implode(' AND ', $arrFilterSelect) . ')';
        }

        if (!$isAdmin && !$globalSearch) {
            $arrFilterUser[] = 'account_userGroupId = :userGroupId';
            $arrFilterUser[] = 'account_userId = :userId';
            $arrFilterUser[] = 'accgroup_groupId = :accgroup_groupId';
            $arrFilterUser[] = 'accuser_userId = :accuser_userId';

            $data['userGroupId'] = $searchFilter['groupId'];
            $data['userId'] = $searchFilter['userId'];
            $data['accgroup_groupId'] = $searchFilter['groupId'];
            $data['accuser_userId'] = $searchFilter['userId'];

            //$arrQueryWhere[] = '(' . implode(' OR ', $arrFilterUser) . ')';
            $arrQueryWhere[] = implode(' OR ', $arrFilterUser);
        }

        $orderDir = ($searchFilter["txtOrder"] == 0) ? 'ASC' : 'DESC';
        $queryOrder = 'ORDER BY ' . $orderKey . ' ' . $orderDir;

        if ($searchFilter['limitCount'] != 99) {
            $queryLimit = 'LIMIT :limitStart,:limitCount';

            $data['limitStart'] = $searchFilter['limitStart'];
            $data['limitCount'] = $searchFilter['limitCount'];
        }

        if (count($arrQueryWhere) === 1) {
            $query = $querySelect . ' WHERE ' . implode($arrQueryWhere) . ' ' . $queryOrder . ' ' . $queryLimit;
        } elseif (count($arrQueryWhere) > 1) {
            $query = $querySelect . ' WHERE ' . implode(' AND ', $arrQueryWhere) . ' ' . $queryOrder . ' ' . $queryLimit;
        } else {
            $query = $querySelect . ' ' . $queryOrder . ' ' . $queryLimit;
        }

        // Obtener el número total de cuentas visibles por el usuario
        DB::setFullRowCount();

        // Obtener los resultados siempre en array de objetos
        DB::setReturnArray();

        // Consulta de la búsqueda de cuentas
        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
//            print_r($query);
//            var_dump($data);
            return false;
        }

        // Obtenemos el número de registros totales de la consulta sin contar el LIMIT
        self::$queryNumRows = DB::$last_num_rows;

        $_SESSION["accountSearchTxt"] = $searchFilter["txtSearch"];
        $_SESSION["accountSearchCustomer"] = $searchFilter["customerId"];
        $_SESSION["accountSearchCategory"] = $searchFilter["categoryId"];
        $_SESSION["accountSearchOrder"] = $searchFilter["txtOrder"];
        $_SESSION["accountSearchKey"] = $searchFilter["keyId"];
        $_SESSION["accountSearchStart"] = $searchFilter["limitStart"];
        $_SESSION["accountSearchLimit"] = $searchFilter["limitCount"];
        $_SESSION["accountGlobalSearch"] = $searchFilter["globalSearch"];

        return $queryRes;
    }

    /**
     * Obtiene el número de cuentas que un usuario puede ver.
     *
     * @return false|int con el número de registros
     */
    public function getAccountMax()
    {
        $data = null;

        if (!Session::getUserIsAdminApp() && !Session::getUserIsAdminAcc()) {
            $query = 'SELECT COUNT(DISTINCT account_id) as numacc '
                . 'FROM accounts '
                . 'LEFT JOIN accGroups ON account_id = accgroup_accountId '
                . 'WHERE account_userGroupId = :userGroupId '
                . 'OR account_userId = :userId '
                . 'OR accgroup_groupId = :groupId';

            $data['userGroupId'] = Session::getUserGroupId();
            $data['groupId'] = Session::getUserGroupId();
            $data['userId'] = Session::getUserId();

        } else {
            $query = "SELECT COUNT(*) as numacc FROM accounts";
        }

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->numacc;
    }
}