<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Account\AccountRequest;
use SP\Account\AccountSearchFilter;
use SP\Account\AccountUtil;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountPassData;
use SP\DataModel\AccountSearchVData;
use SP\DataModel\AccountVData;
use SP\DataModel\Dto\AccountSearchResponse;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Mvc\Model\QueryAssignment;
use SP\Mvc\Model\QueryCondition;
use SP\Mvc\Model\QueryJoin;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Services\Account\AccountPasswordRequest;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountRepository
 *
 * @package Services
 */
class AccountRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Devolver el número total de cuentas
     *
     * @return \stdClass
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
     * @param $id
     *
     * @return AccountPassData
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getPasswordForId($id)
    {
        $queryFilter = AccountUtil::getAccountFilterUser($this->context)
            ->addFilter('Account.id = ?', [$id]);

        $queryData = new QueryData();
        $queryData->setMapClassName(AccountPassData::class);
        $queryData->setLimit(1);
        $queryData->setSelect('Account.id, Account.name, Account.login, Account.pass, Account.key, Account.parentId');
        $queryData->setFrom('Account');
        $queryData->setWhere($queryFilter->getFilters());
        $queryData->setParams($queryFilter->getParams());

        return $this->db->doSelect($queryData)->getData();
    }

    /**
     * @param QueryCondition $queryCondition
     *
     * @return AccountPassData
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getPasswordHistoryForId(QueryCondition $queryCondition)
    {
        $query = /** @lang SQL */
            'SELECT AH.id, AH.name, AH.login, AH.pass, AH.key, AH.parentId 
            FROM AccountHistory AH 
            WHERE ' . $queryCondition->getFilters();

        $queryData = new QueryData();
        $queryData->setMapClassName(AccountPassData::class);
        $queryData->setQuery($query);
        $queryData->setParams($queryCondition->getParams());

        return $this->db->doSelect($queryData)->getData();
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
            otherUserEdit = ?,
            otherUserGroupEdit = ?,
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
            $itemData->otherUserEdit,
            $itemData->otherUserGroupEdit,
            $itemData->isPrivate,
            $itemData->isPrivateGroup,
            $itemData->passDateChange,
            $itemData->parentId
        ]);
        $queryData->setOnErrorMessage(__u('Error al crear la cuenta'));

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
        $queryData->setOnErrorMessage(__u('Error al actualizar la clave'));

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
        $queryData->setOnErrorMessage(__u('Error al actualizar la clave'));

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
            dst.otherUserEdit = src.otherUserEdit + 0,
            dst.otherUserGroupEdit = src.otherUserGroupEdit + 0,
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
        $queryData->setOnErrorMessage(__u('Error al restaurar cuenta'));

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
        $queryData->setOnErrorMessage(__u('Error al eliminar la cuenta'));

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

        if ($itemData->changePermissions) {
            $queryAssignment->addField('otherUserEdit', $itemData->otherUserEdit);
            $queryAssignment->addField('otherUserGroupEdit', $itemData->otherUserGroupEdit);
        }

        $query = /** @lang SQL */
            'UPDATE Account SET ' . $queryAssignment->getAssignments() . ' WHERE id = ?';

        $queryData->setQuery($query);
        $queryData->setParams($queryAssignment->getValues());
        $queryData->addParam($itemData->id);
        $queryData->setOnErrorMessage(__u('Error al modificar la cuenta'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return AccountVData
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT * FROM account_data_v WHERE id = ? LIMIT 1');
        $queryData->setMapClassName(AccountVData::class);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('No se pudieron obtener los datos de la cuenta'));

        $result = $this->db->doSelect($queryData);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('La cuenta no existe'));
        }

        return $result->getData();
    }

    /**
     * Returns all the items
     *
     * @return AccountData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(AccountData::class);
        $queryData->setQuery('SELECT * FROM Account ORDER BY id');

        return $this->db->doSelect($queryData)->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     */
    public function getByIdBatch(array $ids)
    {
        throw new \RuntimeException('Not implemented');
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
        $queryData->setOnErrorMessage(__u('Error al eliminar las cuentas'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
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
        $queryData->setSelect('Account.id, Account.name, C.name AS clientName, C2.name AS categoryName');
        $queryData->setFrom('Account 
        INNER JOIN Client C ON Account.clientId = C.id
        INNER JOIN Category C2 ON Account.categoryId = C2.id');
        $queryData->setOrder('Account.name, C.name');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('Account.name LIKE ? OR C.name LIKE ?');

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
     * @return AccountExtData
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getDataForLink($id)
    {
        $query = /** @lang SQL */
            'SELECT Account.name,
            Account.login,
            Account.pass,
            Account.key,
            Account.url,
            Account.notes,
            C.name AS clientName,
            C2.name AS categoryName
            FROM Account
            INNER JOIN Client C ON Account.clientId = C.id
            INNER JOIN Category C2 ON Account.categoryId = C2.id 
            WHERE Account.id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setMapClassName(AccountExtData::class);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('No se pudieron obtener los datos de la cuenta'));

        $result = $this->db->doSelect($queryData, true);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('La cuenta no existe'));
        }

        return $result->getData();
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param AccountSearchFilter $accountSearchFilter
     *
     * @return AccountSearchResponse
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter)
    {
        $queryFilters = new QueryCondition();

        // Sets the search text depending on if special search filters are being used
        $searchText = $accountSearchFilter->getCleanTxtSearch();

        if (!empty($searchText)) {
            $queryFilters->addFilter('Account.name LIKE ? OR Account.login LIKE ? OR Account.url LIKE ? OR Account.notes LIKE ?', array_fill(0, 4, '%' . $searchText . '%'));
        }

        // Gets special search filters
        $stringFilters = $accountSearchFilter->getStringFilters();

        if ($stringFilters->hasFilters()) {
            $queryFilters->addFilter($stringFilters->getFilters(), $stringFilters->getParams());
        }

        if (!empty($accountSearchFilter->getCategoryId())) {
            $queryFilters->addFilter('Account.categoryId = ?', [$accountSearchFilter->getCategoryId()]);
        }

        if (!empty($accountSearchFilter->getClientId())) {
            $queryFilters->addFilter('Account.clientId = ?', [$accountSearchFilter->getClientId()]);
        }

        $where = [];

        $queryFilterUser = AccountUtil::getAccountFilterUser($this->context, $accountSearchFilter->getGlobalSearch());

        if ($queryFilterUser->hasFilters()) {
            $where[] = $queryFilterUser->getFilters();
        }

        $queryData = new QueryData();
        $queryJoins = new QueryJoin();

        if ($accountSearchFilter->isSearchFavorites() === true) {
            $queryJoins->addJoin('INNER JOIN AccountToFavorite AF ON (AF.accountId = Account.id AND AF.userId = ?)', [$this->context->getUserData()->getId()]);
        }

        if ($accountSearchFilter->hasTags()) {
            $queryJoins->addJoin('INNER JOIN AccountToTag AT ON AT.accountId = Account.id');
            $queryFilters->addFilter('AT.tagId IN (' . $this->getParamsFromArray($accountSearchFilter->getTagsId()) . ')', $accountSearchFilter->getTagsId());

            if (QueryCondition::CONDITION_AND === $accountSearchFilter->getFilterOperator()) {
                $queryData->setGroupBy('Account.id HAVING COUNT(DISTINCT AT.tagId) = ' . count($accountSearchFilter->getTagsId()));
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

        return new AccountSearchResponse($this->db->getFullRowCount($queryData), $this->db->doSelect($queryData)->getDataAsArray());
    }

    /**
     * @param QueryCondition $queryFilter
     *
     * @return array
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

        return $this->db->doSelect($queryData)->getDataAsArray();
    }

    /**
     * @param QueryCondition $queryFilter
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getLinked(QueryCondition $queryFilter)
    {
        $query = /** @lang SQL */
            'SELECT Account.id, Account.name, C.name AS clientName 
            FROM Account
            INNER JOIN Client C ON Account.clientId = C.id 
            WHERE ' . $queryFilter->getFilters() . ' ORDER  BY name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($queryFilter->getParams());

        return $this->db->doSelect($queryData)->getDataAsArray();
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