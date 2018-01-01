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

namespace SP\Services\Account;

use SP\Account\AccountUtil;
use SP\Config\ConfigDB;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\AccountPassData;
use SP\DataModel\ItemSearchData;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class AccountHistoryService
 *
 * @package Services
 */
class AccountHistoryService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param $id
     * @return array|false Con los registros con id como clave y fecha - usuario como valor
     */
    public function getHistoryForAccount($id)
    {
        $query = /** @lang SQL */
            'SELECT acchistory_id,'
            . 'acchistory_dateEdit,'
            . 'u1.user_login AS user_edit,'
            . 'u2.user_login AS user_add,'
            . 'acchistory_dateAdd '
            . 'FROM accHistory '
            . 'LEFT JOIN usrData u1 ON acchistory_userEditId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userId = u2.user_id '
            . 'WHERE acchistory_accountId = ? '
            . 'ORDER BY acchistory_id DESC';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $items = [];

        foreach (DbWrapper::getResultsArray($Data, $this->db) as $history) {
            // Comprobamos si la entrada en el historial es la primera (no tiene editor ni fecha de edición)
            if (empty($history->acchistory_dateEdit) || $history->acchistory_dateEdit === '0000-00-00 00:00:00') {
                $date = $history->acchistory_dateAdd . ' - ' . $history->user_add;
            } else {
                $date = $history->acchistory_dateEdit . ' - ' . $history->user_edit;
            }

            $items[$history->acchistory_id] = $date;
        }

        return $items;
    }

    /**
     * @param $id
     * @return AccountPassData
     */
    public function getPasswordForHistoryId($id)
    {
        $Data = new QueryData();
        $Data->setMapClassName(AccountPassData::class);
        $Data->setLimit(1);

        $Data->setSelect('acchistory_id AS account_id, acchistory_name AS account_name, acchistory_login AS account_login, acchistory_pass AS account_pass, acchistory_key AS account_key, acchistory_parentId  AS account_parentId');
        $Data->setFrom('accHistory');

        $queryWhere = AccountUtil::getAccountHistoryFilterUser($Data, $this->session);
        $queryWhere[] = 'acchistory_id = ?';
        $Data->addParam($id);

        $Data->setWhere($queryWhere);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * @param array $items array of ['id' => <int>, 'isDelete' => <bool>]
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function createBatch(array $items)
    {
        foreach ($items as $item) {
            $this->create($item);
        }
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param array $itemData ['id' => <int>, 'isDelete' => <bool>]
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create($itemData)
    {
        $Data = new QueryData();
        $Data->addParam(($itemData['isDelete'] === false) ? 1 : 0);
        $Data->addParam(($itemData['isDelete'] === true) ? 1 : 0);
        $Data->addParam(ConfigDB::getValue('masterPwd'));


        $querySelect = /** @lang SQL */
            'SELECT account_id,'
            . 'account_categoryId,'
            . 'account_customerId,'
            . 'account_name,'
            . 'account_login,'
            . 'account_url,'
            . 'account_pass,'
            . 'account_key,'
            . 'account_notes,'
            . 'account_countView,'
            . 'account_countDecrypt,'
            . 'account_dateAdd,'
            . 'account_dateEdit,'
            . 'account_userId,'
            . 'account_userGroupId,'
            . 'account_userEditId,'
            . 'account_otherUserEdit,'
            . 'account_otherGroupEdit,'
            . 'account_isPrivate,'
            . 'account_isPrivateGroup,'
            . '?,?,? '
            . 'FROM accounts WHERE account_id = ?';

        $Data->addParam($itemData['id']);

        $query = /** @lang SQL */
            'INSERT INTO accHistory '
            . '(acchistory_accountId,'
            . 'acchistory_categoryId,'
            . 'acchistory_customerId,'
            . 'acchistory_name,'
            . 'acchistory_login,'
            . 'acchistory_url,'
            . 'acchistory_pass,'
            . 'acchistory_key,'
            . 'acchistory_notes,'
            . 'acchistory_countView,'
            . 'acchistory_countDecrypt,'
            . 'acchistory_dateAdd,'
            . 'acchistory_dateEdit,'
            . 'acchistory_userId,'
            . 'acchistory_userGroupId,'
            . 'acchistory_userEditId,'
            . 'acchistory_otherUserEdit,'
            . 'acchistory_otherGroupEdit,'
            . 'accHistory_isPrivate,'
            . 'accHistory_isPrivateGroup,'
            . 'acchistory_isModify,'
            . 'acchistory_isDeleted,'
            . 'acchistory_mPassHash)';

        $Data->setQuery($query . ' ' . $querySelect);
        $Data->setOnErrorMessage(__u('Error al actualizar el historial'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * @param array $ids
     * @throws SPException
     */
    public function deleteBatch(array $ids)
    {
        foreach ($ids as $id) {
            $this->delete($id);
        }
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param array|int $id
     * @return bool Los ids de las cuentas eliminadas
     * @throws SPException
     */
    public function delete($id)
    {
        $Data = new QueryData();

        $query = /** @lang SQL */
            'DELETE FROM accHistory WHERE acchistory_id = ? LIMIT 1';

        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar la cuenta'));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * Updates an item
     *
     * @param mixed $itemData
     * @return mixed
     */
    public function update($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return AccountHistoryData
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT acchistory_accountId AS account_id,'
            . 'acchistory_customerId AS account_customerId,'
            . 'acchistory_categoryId AS account_categoryId,'
            . 'acchistory_name AS account_name,'
            . 'acchistory_login AS account_login,'
            . 'acchistory_url AS account_url,'
            . 'acchistory_pass AS account_pass,'
            . 'acchistory_key AS account_key,'
            . 'acchistory_notes AS account_notes,'
            . 'acchistory_countView AS account_countView,'
            . 'acchistory_countDecrypt AS account_countDecrypt,'
            . 'acchistory_dateAdd AS account_dateAdd,'
            . 'acchistory_dateEdit AS account_dateEdit,'
            . 'acchistory_userId AS account_userId,'
            . 'acchistory_userGroupId AS account_userGroupId,'
            . 'acchistory_userEditId AS account_userEditId,'
            . 'acchistory_isModify,'
            . 'acchistory_isDeleted,'
            . 'acchistory_otherUserEdit + 0 AS account_otherUserEdit,'
            . 'acchistory_otherGroupEdit + 0 AS account_otherGroupEdit,'
            . 'acchistory_isPrivate + 0 AS account_isPrivate,'
            . 'acchistory_isPrivateGroup + 0 AS account_isPrivateGroup,'
            . 'u1.user_name,'
            . 'u1.user_login,'
            . 'usergroup_name,'
            . 'u2.user_name AS user_editName,'
            . 'u2.user_login AS user_editLogin,'
            . 'category_name, customer_name '
            . 'FROM accHistory '
            . 'LEFT JOIN categories ON acchistory_categoryId = category_id '
            . 'LEFT JOIN usrGroups ON acchistory_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON acchistory_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON acchistory_customerId = customer_id '
            . 'WHERE acchistory_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName(AccountHistoryData::class);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, __u('No se pudieron obtener los datos de la cuenta'));
        }

        return $queryRes;
    }

    /**
     * Returns all the items
     *
     * @return array
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT acchistory_id,'
            . 'acchistory_dateEdit,'
            . 'u1.user_login AS user_edit,'
            . 'u2.user_login AS user_add,'
            . 'acchistory_dateAdd '
            . 'FROM accHistory '
            . 'LEFT JOIN usrData u1 ON acchistory_userEditId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userId = u2.user_id '
            . 'ORDER BY acchistory_id DESC';

        $Data = new QueryData();
        $Data->setQuery($query);

        $items = [];

        foreach (DbWrapper::getResultsArray($Data, $this->db) as $history) {
            // Comprobamos si la entrada en el historial es la primera (no tiene editor ni fecha de edición)
            if (empty($history->acchistory_dateEdit) || $history->acchistory_dateEdit === '0000-00-00 00:00:00') {
                $date = $history->acchistory_dateAdd . ' - ' . $history->user_add;
            } else {
                $date = $history->acchistory_dateEdit . ' - ' . $history->user_edit;
            }

            $items[$history->acchistory_id] = $date;
        }

        return $items;
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function getByIdBatch(array $ids)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function deleteByIdBatch(array $ids)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
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

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}