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
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountPassData;
use SP\DataModel\AccountSearchVData;
use SP\DataModel\AccountVData;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Mvc\Model\QueryAssignment;
use SP\Mvc\Model\QueryCondition;
use SP\Mvc\Model\QueryJoin;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Services\Account\AccountPasswordRequest;
use SP\Services\Account\AccountRequest;
use SP\Services\Account\AccountSearchFilter;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;
use stdClass;

/**
 * Class AccountRepository
 *
 * @package Services
 */
final class AccountRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Devolver el número total de cuentas
     *
     * @return stdClass
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getTotalNumAccounts()
    {
        $query = /** @lang SQL */
            'SELECT SUM(n) AS num FROM 
            (SELECT COUNT(*) AS n FROM Account UNION SELECT COUNT(*) AS n FROM AccountHistory) a';

        $queryData = new QueryData();
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData)->getData();
    }

    /**
     * @param                $id
     * @param QueryCondition $queryCondition
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getPasswordForId($id, QueryCondition $queryCondition)
    {
        $queryCondition->addFilter('Account.id = ?', [$id]);

        $queryData = new QueryData();
        $queryData->setMapClassName(AccountPassData::class);
        $queryData->setLimit(1);
        $queryData->setSelect('Account.id, Account.name, Account.login, Account.pass, Account.key, Account.parentId');
        $queryData->setFrom('Account');
        $queryData->setWhere($queryCondition->getFilters());
        $queryData->setParams($queryCondition->getParams());

        return $this->db->doSelect($queryData);
    }

    /**
     * @param QueryCondition $queryCondition
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getPasswordHistoryForId(QueryCondition $queryCondition)
    {
        $query = /** @lang SQL */
            'SELECT 
              AccountHistory.id, 
              AccountHistory.name,
              AccountHistory.login,
              AccountHistory.pass,
              AccountHistory.key,
              AccountHistory.parentId,
              AccountHistory.mPassHash 
            FROM AccountHistory 
            WHERE ' . $queryCondition->getFilters();

        $queryData = new QueryData();
        $queryData->setMapClassName(AccountPassData::class);
        $queryData->setQuery($query);
        $queryData->setParams($queryCondition->getParams());

        return $this->db->doSelect($queryData);
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param int $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementDecryptCounter($id)
    {
        $query = /** @lang SQL */
            'UPDATE Account SET countDecrypt = (countDecrypt + 1) WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountRequest $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO Account SET 
            clientId = ?,
            categoryId = ?,
            `name` = ?,
            login = ?,
            url = ?,
            pass = ?,
            `key` = ?,
            notes = ?,
            dateAdd = NOW(),
            userId = ?,
            userGroupId = ?,
            userEditId = ?,
            isPrivate = ?,
            isPrivateGroup = ?,
            passDate = UNIX_TIMESTAMP(),
            passDateChange = ?,
            parentId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->clientId,
            $itemData->categoryId,
            $itemData->name,
            $itemData->login,
            $itemData->url,
            $itemData->pass,
            $itemData->key,
            $itemData->notes,
            $itemData->userId,
            $itemData->userGroupId,
            $itemData->userId,
            $itemData->isPrivate,
            $itemData->isPrivateGroup,
            $itemData->passDateChange,
            $itemData->parentId
        ]);
        $queryData->setOnErrorMessage(__u('Error while creating the account'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param AccountRequest $accountRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function editPassword(AccountRequest $accountRequest)
    {
        $query = /** @lang SQL */
            'UPDATE Account SET 
            pass = ?,
            `key` = ?,
            userEditId = ?,
            dateEdit = NOW(),
            passDate = UNIX_TIMESTAMP(),
            passDateChange = ?
            WHERE id = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $accountRequest->pass,
            $accountRequest->key,
            $accountRequest->userEditId,
            $accountRequest->passDateChange,
            $accountRequest->id
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
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
            'UPDATE Account SET 
            pass = ?,
            `key` = ?
            WHERE id = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$request->pass, $request->key, $request->id]);
        $queryData->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param int $historyId El Id del registro en el histórico
     * @param int $userId    User's Id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function editRestore($historyId, $userId)
    {
        $query = /** @lang SQL */
            'UPDATE Account dst, 
            (SELECT * FROM AccountHistory AH WHERE AH.id = ?) src SET 
            dst.clientId = src.clientId,
            dst.categoryId = src.categoryId,
            dst.name = src.name,
            dst.login = src.login,
            dst.url = src.url,
            dst.notes = src.notes,
            dst.userGroupId = src.userGroupId,
            dst.userEditId = ?,
            dst.dateEdit = NOW(),
            dst.pass = src.pass,
            dst.key = src.key,
            dst.passDate = src.passDate,
            dst.passDateChange = src.passDateChange, 
            dst.parentId = src.parentId, 
            dst.isPrivate = src.isPrivate,
            dst.isPrivateGroup = src.isPrivateGroup
            WHERE dst.id = src.accountId';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$historyId, $userId]);
        $queryData->setOnErrorMessage(__u('Error on restoring the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $id
     *
     * @return int EL número de cuentas eliminadas
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();

        $queryData->setQuery('DELETE FROM Account WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Updates an item
     *
     * @param AccountRequest $itemData
     *
     * @return mixed
     * @throws SPException
     */
    public function update($itemData)
    {
        $queryAssignment = new QueryAssignment();

        $queryAssignment->setFields([
            'clientId',
            'categoryId',
            'name',
            'login',
            'url',
            'notes',
            'userEditId',
            'dateEdit = NOW()',
            'passDateChange',
            'isPrivate',
            'isPrivateGroup',
            'parentId'
        ], [
            $itemData->clientId,
            $itemData->categoryId,
            $itemData->name,
            $itemData->login,
            $itemData->url,
            $itemData->notes,
            $itemData->userEditId,
            $itemData->passDateChange,
            $itemData->isPrivate,
            $itemData->isPrivateGroup,
            $itemData->parentId
        ]);


        $queryData = new QueryData();

        if ($itemData->changeUserGroup) {
            $queryAssignment->addField('userGroupId', $itemData->userGroupId);
        }

        if ($itemData->changeOwner) {
            $queryAssignment->addField('userId', $itemData->userId);
        }

        $query = /** @lang SQL */
            'UPDATE Account SET ' . $queryAssignment->getAssignments() . ' WHERE id = ?';

        $queryData->setQuery($query);
        $queryData->setParams($queryAssignment->getValues());
        $queryData->addParam($itemData->id);
        $queryData->setOnErrorMessage(__u('Error while updating the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Updates an item for bulk action
     *
     * @param AccountRequest $itemData
     *
     * @return mixed
     * @throws SPException
     */
    public function updateBulk($itemData)
    {
        $queryAssignment = new QueryAssignment();

        $queryAssignment->setFields([
            'userEditId',
            'dateEdit = NOW()'
        ], [
            $itemData->userEditId,
        ]);

        $queryData = new QueryData();

        $optional = ['clientId', 'categoryId', 'userId', 'userGroupId', 'passDateChange'];

        $optionalCount = 0;

        foreach ($optional as $field) {
            if (isset($itemData->{$field}) && !empty($itemData->{$field})) {
                $queryAssignment->addField($field, $itemData->{$field});
                $optionalCount++;
            } else {
                logger(sprintf('Field \'%s\' not found in $itemData', $field), 'ERROR');
            }
        }

        if ($optionalCount === 0) {
            return 0;
        }

        $query = /** @lang SQL */
            'UPDATE Account SET ' . $queryAssignment->getAssignments() . ' WHERE id = ?';

        $queryData->setQuery($query);
        $queryData->setParams($queryAssignment->getValues());
        $queryData->addParam($itemData->id);
        $queryData->setOnErrorMessage(__u('Error while updating the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT * FROM account_data_v WHERE id = ? LIMIT 1');
        $queryData->setMapClassName(AccountVData::class);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(AccountData::class);
        $queryData->setQuery('SELECT * FROM Account ORDER BY id');

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
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

        $queryData->setQuery('DELETE FROM Account WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the accounts'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse($id)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
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
        $queryData->setSelect('id, name, clientName, categoryName, userName, userGroupName');
        $queryData->setFrom('account_search_v');
        $queryData->setOrder('name, clientName');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ? 
            OR clientName LIKE ? 
            OR categoryName LIKE ? 
            OR userName LIKE ? 
            OR userGroupName LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @param int $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementViewCounter($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Account SET countView = (countView + 1) WHERE id = ? LIMIT 1');
        $queryData->addParam($id);

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @param $id
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getDataForLink($id)
    {
        $query = /** @lang SQL */
            'SELECT Account.id, 
            Account.name,
            Account.login,
            Account.pass,
            Account.key,
            Account.url,
            Account.notes,
            Client.name AS clientName,
            Category.name AS categoryName
            FROM Account
            INNER JOIN Client ON Account.clientId = Client.id
            INNER JOIN Category ON Account.categoryId = Category.id 
            WHERE Account.id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setMapClassName(AccountExtData::class);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param AccountSearchFilter $accountSearchFilter
     * @param QueryCondition      $queryFilterUser
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter, QueryCondition $queryFilterUser)
    {
        $queryFilters = new QueryCondition();

        // Sets the search text depending on if special search filters are being used
        $searchText = $accountSearchFilter->getCleanTxtSearch();

        if (!empty($searchText)) {
            $queryFilters->addFilter(
                'Account.name LIKE ? OR Account.login LIKE ? OR Account.url LIKE ? OR Account.notes LIKE ?',
                array_fill(0, 4, '%' . $searchText . '%')
            );
        }

        // Gets special search filters
        $stringFilters = $accountSearchFilter->getStringFilters();

        if ($stringFilters->hasFilters()) {
            $queryFilters->addFilter($stringFilters->getFilters($accountSearchFilter->getFilterOperator()), $stringFilters->getParams());
        }

        if (!empty($accountSearchFilter->getCategoryId())) {
            $queryFilters->addFilter('Account.categoryId = ?', [$accountSearchFilter->getCategoryId()]);
        }

        if (!empty($accountSearchFilter->getClientId())) {
            $queryFilters->addFilter('Account.clientId = ?', [$accountSearchFilter->getClientId()]);
        }

        $where = [];

        if ($queryFilterUser->hasFilters()) {
            $where[] = $queryFilterUser->getFilters();
        }

        $queryData = new QueryData();
        $queryJoins = new QueryJoin();

        if ($accountSearchFilter->isSearchFavorites() === true) {
            $queryJoins->addJoin(
                'INNER JOIN AccountToFavorite ON (AccountToFavorite.accountId = Account.id AND AccountToFavorite.userId = ?)',
                [$this->context->getUserData()->getId()]
            );
        }

        if ($accountSearchFilter->hasTags()) {
            $queryJoins->addJoin('INNER JOIN AccountToTag ON AccountToTag.accountId = Account.id');
            $queryFilters->addFilter(
                'AccountToTag.tagId IN (' . $this->getParamsFromArray($accountSearchFilter->getTagsId()) . ')',
                $accountSearchFilter->getTagsId()
            );

            if (QueryCondition::CONDITION_AND === $accountSearchFilter->getFilterOperator()) {
                $queryData->setGroupBy('Account.id HAVING COUNT(DISTINCT AccountToTag.tagId) = ' . count($accountSearchFilter->getTagsId()));
            }
        }

        if ($queryFilters->hasFilters()) {
            $where[] = $queryFilters->getFilters($accountSearchFilter->getFilterOperator());
        }

        $queryData->setWhere($where);
        $queryData->setParams(array_merge($queryJoins->getParams(), $queryFilterUser->getParams(), $queryFilters->getParams()));
        $queryData->setSelect('DISTINCT Account.*');
        $queryData->setFrom('account_search_v Account ' . $queryJoins->getJoins());
        $queryData->setOrder($accountSearchFilter->getOrderString());

        if ($accountSearchFilter->getLimitCount() > 0) {
            $queryLimit = '?, ?';

            $queryData->addParam($accountSearchFilter->getLimitStart());
            $queryData->addParam($accountSearchFilter->getLimitCount());
            $queryData->setLimit($queryLimit);
        }

        $queryData->setMapClassName(AccountSearchVData::class);

        return $this->db->doSelect($queryData, true);
    }

    /**
     * @param QueryCondition $queryFilter
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUser(QueryCondition $queryFilter)
    {
        $query = /** @lang SQL */
            'SELECT Account.id, Account.name, C.name AS clientName 
            FROM Account
            LEFT JOIN Client C ON Account.clientId = C.id 
            WHERE ' . $queryFilter->getFilters() . ' ORDER BY name';

        $queryData = new QueryData();
        $queryData->setMapClassName(ItemData::class);
        $queryData->setQuery($query);
        $queryData->setParams($queryFilter->getParams());

        return $this->db->doSelect($queryData);
    }

    /**
     * @param QueryCondition $queryFilter
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getLinked(QueryCondition $queryFilter)
    {
        $query = /** @lang SQL */
            'SELECT Account.id, Account.name, Client.name AS clientName 
            FROM Account
            INNER JOIN Client ON Account.clientId = Client.id 
            WHERE ' . $queryFilter->getFilters() . ' ORDER  BY Account.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($queryFilter->getParams());

        return $this->db->doSelect($queryData);
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return array Con los datos de la clave
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAccountsPassData()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, `name`, pass, `key` FROM Account WHERE BIT_LENGTH(pass) > 0');

        return $this->db->doSelect($queryData)->getDataAsArray();
    }
}