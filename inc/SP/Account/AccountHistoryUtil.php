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
class AccountHistoryUtil
{
    /**
     * Devolver el nombre de la cuenta a partir del Id
     *
     * @param int $accountId El Id de la cuenta
     * @return string|bool
     */
    public static function getAccountNameById($accountId)
    {
        $query = /** @lang SQL */
            'SELECT acchistory_name FROM accHistory WHERE acchistory_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        $queryRes = DB::getResults($Data);

        return is_object($queryRes) ? $queryRes : false;
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
            'SELECT acchistory_name FROM accHistory WHERE acchistory_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';

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
        $Data->setSelect('acchistory_id, acchistory_name, customer_name, IFNULL(acchistory_dateEdit,acchistory_dateAdd) as acchistory_date, BIN(acchistory_isModify) as acchistory_isModify, BIN(acchistory_isDeleted) as acchistory_isDeleted');
        $Data->setFrom('accHistory LEFT JOIN customers ON acchistory_customerId = customer_id');
        $Data->setOrder('acchistory_name, customer_name, acchistory_id DESC');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('acchistory_name LIKE ? OR customer_name LIKE ?');

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
     * Restaurar una cuenta desde el histórico.
     *
     * @param $id int El Id del registro en el histórico
     * @param $accountId
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function restoreFromHistory($id, $accountId)
    {
        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($accountId, false);

        $query = /** @lang SQL */
            'UPDATE accounts dst, '
            . '(SELECT * FROM accHistory WHERE acchistory_id = :id LIMIT 1) src SET '
            . 'dst.account_customerId = src.acchistory_customerId,'
            . 'dst.account_categoryId = src.acchistory_categoryId,'
            . 'dst.account_name = src.acchistory_name,'
            . 'dst.account_login = src.acchistory_login,'
            . 'dst.account_url = src.acchistory_url,'
            . 'dst.account_notes = src.acchistory_notes,'
            . 'dst.account_userGroupId = src.acchistory_userGroupId,'
            . 'dst.account_userEditId = :accountUserEditId,'
            . 'dst.account_dateEdit = NOW(),'
            . 'dst.account_otherUserEdit = src.acchistory_otherUserEdit + 0,'
            . 'dst.account_otherGroupEdit = src.acchistory_otherGroupEdit + 0,'
            . 'dst.account_pass = src.acchistory_pass,'
            . 'dst.account_key = src.acchistory_key,'
            . 'dst.account_passDate = src.acchistory_passDate,'
            . 'dst.account_passDateChange = src.acchistory_passDateChange, '
            . 'dst.account_parentId = src.acchistory_parentId, '
            . 'dst.account_isPrivate = src.accHistory_isPrivate, '
            . 'dst.account_isPrivateGroup = src.accHistory_isPrivateGroup '
            . 'WHERE dst.account_id = src.acchistory_accountId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');
        $Data->addParam(Session::getUserData()->getUserId(), 'accountUserEditId');
        $Data->setOnErrorMessage(__('Error al restaurar cuenta', false));

        DB::getQuery($Data);

        return true;
    }
}