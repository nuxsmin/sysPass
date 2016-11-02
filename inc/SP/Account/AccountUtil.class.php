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

use SP\DataModel\ItemSearchData;
use SP\Storage\DB;
use SP\Core\Exceptions\SPException;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
     * @param int $accountId con el Id de la cuenta
     * @return false|object con el id de usuario y modificador.
     */
    public static function getAccountRequestData($accountId)
    {
        $query = 'SELECT account_userId,'
            . 'account_userEditId,'
            . 'account_name,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

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
        $query = 'SELECT user_name '
            . 'FROM accUsers '
            . 'JOIN usrData ON accuser_userId = user_id '
            . 'WHERE accuser_accountId = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        if (!is_array($queryRes)) {
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
        $query = 'SELECT account_id,'
            . 'account_name,'
            . 'account_categoryId,'
            . 'account_customerId,'
            . 'account_login,'
            . 'account_url,'
            . 'account_pass,'
            . 'account_IV,'
            . 'account_notes '
            . 'FROM accounts';

        $Data = new QueryData();
        $Data->setQuery($query);

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, _('No se pudieron obtener los datos de las cuentas'));
        }

        return $queryRes;
    }

    /**
     * Devolver el nombre de la cuenta a partir del Id
     *
     * @param int $accountId El Id de la cuenta
     * @return string|bool
     */
    public static function getAccountNameById($accountId)
    {
        $query = 'SELECT account_name FROM accounts WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        $queryRes = DB::getResults($Data);

        return ($queryRes !== false) ? $queryRes->account_name : false;
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


        $query = 'SELECT account_id,'
            . 'account_name,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id';

        if ($SearchData->getSeachString() !== '') {
            $search = '%' . $SearchData->getSeachString() . '%';

            $query .= ' WHERE account_name LIKE ? '
                . 'OR customer_name LIKE ?';

            $Data->addParam($search);
            $Data->addParam($search);
        }

        $query .= ' ORDER BY account_name';
        $query .= ' LIMIT ?, ?';

        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        $Data->setQuery($query);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}