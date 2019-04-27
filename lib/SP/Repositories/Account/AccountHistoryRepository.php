<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\Account;

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountHistoryCreateDto;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Services\Account\AccountPasswordRequest;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountHistoryRepository
 *
 * @package Services
 */
final class AccountHistoryRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param $id
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getHistoryForAccount($id)
    {
        $query = /** @lang SQL */
            'SELECT AH.id,
            AH.dateEdit,
            AH.dateAdd ,
            U1.login AS userAdd,
            U2.login AS userEdit
            FROM AccountHistory AH
            INNER JOIN User U1 ON AH.userId = U1.id
            LEFT JOIN User U2 ON AH.userEditId = U2.id
            WHERE AH.accountId = ?
            ORDER BY AH.id DESC';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountHistoryCreateDto $dto
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create($dto)
    {
        $queryData = new QueryData();
        $query = /** @lang SQL */
            'INSERT INTO AccountHistory
            (accountId,
            categoryId,
            clientId,
            `name`,
            login,
            url,
            pass,
            `key`,
            notes,
            countView,
            countDecrypt,
            dateAdd,
            dateEdit,
            userId,
            userGroupId,
            userEditId,
            otherUserEdit,
            otherUserGroupEdit,
            isPrivate,
            isPrivateGroup,
            isModify,
            isDeleted,
            mPassHash)
            SELECT id,
            categoryId,
            clientId,
            `name`,
            login,
            url,
            pass,
            `key`,
            notes,
            countView,
            countDecrypt,
            dateAdd,
            dateEdit,
            userId,
            userGroupId,
            userEditId,
            otherUserEdit,
            otherUserGroupEdit,
            isPrivate,
            isPrivateGroup,
            ?,?,? FROM Account WHERE id = ?';

        $queryData->setQuery($query);
        $queryData->addParam((int)$dto->isModify());
        $queryData->addParam((int)$dto->isDelete());
        $queryData->addParam($dto->getMasterPassHash());
        $queryData->addParam($dto->getAccountId());
        $queryData->setOnErrorMessage(__u('Error while updating history'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $id
     *
     * @return bool Los ids de las cuentas eliminadas
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountHistory WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Updates an item
     *
     * @param mixed $itemData
     *
     * @return mixed
     */
    public function update($itemData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT AH.id,
            AH.accountId,
            AH.clientId,
            AH.categoryId,
            AH.name,
            AH.login,
            AH.url,
            AH.pass,
            AH.key,
            AH.notes,
            AH.countView,
            AH.countDecrypt,
            AH.dateAdd,
            AH.dateEdit,
            AH.userId,
            AH.userGroupId,
            AH.userEditId,
            AH.isModify,
            AH.isDeleted,
            AH.otherUserEdit,
            AH.otherUserGroupEdit,
            AH.isPrivate,
            AH.isPrivateGroup,
            U1.name AS userName,
            U1.login AS userLogin,
            UG.name AS userGroupName,
            U2.name AS userEditName,
            U2.login AS userEditLogin,
            C.name AS categoryName,
            C2.name AS clientName
            FROM AccountHistory AH
            INNER JOIN Category C ON AH.categoryId = C.id
            INNER JOIN Client C2 ON AH.clientId = C2.id
            INNER JOIN UserGroup UG ON AH.userGroupId = UG.id
            INNER JOIN User U1 ON AH.userId = U1.id
            LEFT JOIN User U2 ON AH.userEditId = U2.id
            WHERE AH.id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setMapClassName(AccountHistoryData::class);
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT AH.id,
            AH.dateEdit,
            AH.dateAdd, 
            U1.login AS userAdd,
            U2.login AS userEdit
            FROM AccountHistory AH
            INNER JOIN User U1 ON AH.userId = U1.id 
            LEFT JOIN User U2 ON AH.userEditId = U2.id 
            ORDER BY AH.id DESC';

        $queryData = new QueryData();
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return void
     */
    public function getByIdBatch(array $ids)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountHistory WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the accounts'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes all the items for given accounts id
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountIdBatch(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountHistory WHERE accountId IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the accounts'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return void
     */
    public function checkInUse($id)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setSelect('AH.id, AH.name, C.name as clientName, C2.name as categoryName, IFNULL(AH.dateEdit,AH.dateAdd) as date, AH.isModify, AH.isDeleted');
        $queryData->setFrom('AccountHistory AH 
        INNER JOIN Client C ON AH.clientId = C.id
        INNER JOIN Category C2 ON AH.categoryId = C2.id
        ');
        $queryData->setOrder('date DESC, AH.name, C.name, AH.id DESC');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('AH.name LIKE ? OR C.name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($itemSearchData->getLimitStart());
        $queryData->addParam($itemSearchData->getLimitCount());

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAccountsPassData()
    {
        $query = /** @lang SQL */
            'SELECT id, `name`, pass, `key`, mPassHash
            FROM AccountHistory WHERE BIT_LENGTH(pass) > 0
            ORDER BY id';

        $queryData = new QueryData();
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param AccountPasswordRequest $request
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassword(AccountPasswordRequest $request)
    {
        $query = /** @lang SQL */
            'UPDATE AccountHistory SET 
            pass = ?,
            `key` = ?,
            mPassHash = ?
            WHERE id = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $request->pass,
            $request->key,
            $request->hash,
            $request->id
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }
}