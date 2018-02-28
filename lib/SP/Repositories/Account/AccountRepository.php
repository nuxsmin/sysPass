<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Services\Account\AccountPasswordRequest;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

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
     */
    public function getTotalNumAccounts()
    {
        $query = /** @lang SQL */
            'SELECT SUM(n) AS num FROM 
            (SELECT COUNT(*) AS n FROM Account UNION SELECT COUNT(*) AS n FROM AccountHistory) a';

        $queryData = new QueryData();
        $queryData->setQuery($query);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * @param $id
     * @return AccountPassData
     */
    public function getPasswordForId($id)
    {
        $queryFilter = AccountUtil::getAccountFilterUser($this->session)
            ->addFilter('A.id = ?', [$id]);

        $queryData = new QueryData();
        $queryData->setMapClassName(AccountPassData::class);
        $queryData->setLimit(1);
        $queryData->setSelect('A.id, A.name, A.login, A.pass, A.key, A.parentId');
        $queryData->setFrom('Account A');
        $queryData->setWhere($queryFilter->getFilters());
        $queryData->setParams($queryFilter->getParams());

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * @param QueryCondition $queryCondition
     * @return ItemData
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

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param int $id
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementDecryptCounter($id)
    {
        $query = /** @lang SQL */
            'UPDATE Account SET countDecrypt = (countDecrypt + 1) WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountRequest $itemData
     * @return int
     * @throws QueryException
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO Account SET 
            clientId = ?,
            categoryId = ?,
            name = ?,
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

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param AccountRequest $accountRequest
     * @return bool
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
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
        $queryData->addParam($accountRequest->pass);
        $queryData->addParam($accountRequest->key);
        $queryData->addParam($accountRequest->userEditId);
        $queryData->addParam($accountRequest->passDateChange);
        $queryData->addParam($accountRequest->id);
        $queryData->setOnErrorMessage(__u('Error al actualizar la clave'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param AccountPasswordRequest $request
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
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
        $queryData->addParam($request->pass);
        $queryData->addParam($request->key);
        $queryData->addParam($request->id);
        $queryData->setOnErrorMessage(__u('Error al actualizar la clave'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param int $historyId El Id del registro en el histórico
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function editRestore($historyId)
    {
        $query = /** @lang SQL */
            'UPDATE Account dst, 
            (SELECT * FROM AccountHistory AH WHERE AH.id = :id) src SET 
            dst.clientId = src.clientId,
            dst.categoryId = src.categoryId,
            dst.name = src.name,
            dst.login = src.login,
            dst.url = src.url,
            dst.notes = src.notes,
            dst.userGroupId = src.userGroupId,
            dst.userEditId = :userEditId,
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

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($historyId, 'id');
        $Data->addParam($this->session->getUserData()->getId(), 'userEditId');
        $Data->setOnErrorMessage(__u('Error al restaurar cuenta'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $id
     * @return bool Los ids de las cuentas eliminadas
     * @throws SPException
     */
    public function delete($id)
    {
        $queryData = new QueryData();

        $queryData->setQuery('DELETE FROM Account WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar la cuenta'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Updates an item
     *
     * @param AccountRequest $itemData
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

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return AccountVData
     * @throws SPException
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT * FROM account_data_v WHERE id = ? LIMIT 1');
        $queryData->setMapClassName(AccountVData::class);
        $queryData->addParam($id);

        /** @var AccountVData|array $queryRes */
        $queryRes = DbWrapper::getResults($queryData, $this->db);

        if ($queryRes === false) {
            throw new SPException(__u('No se pudieron obtener los datos de la cuenta'), SPException::CRITICAL);
        }

        if (is_array($queryRes) && count($queryRes) === 0) {
            throw new SPException(__u('La cuenta no existe'), SPException::CRITICAL);
        }

        return $queryRes;
    }

    /**
     * Returns all the items
     *
     * @return AccountData[]
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(AccountData::class);
        $queryData->setQuery('SELECT * FROM Account A ORDER BY id');

        return DbWrapper::getResultsArray($queryData, $this->db);
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
     * @return int
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();

        $queryData->setQuery('DELETE FROM Account WHERE id IN (' . $this->getParamsFromArray($ids) . ') LIMIT 1');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar las cuentas'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
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
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $queryData = new QueryData();
        $queryData->setSelect('A.id, A.name, C.name AS clientName');
        $queryData->setFrom('Account A INNER JOIN Client C ON A.clientId = C.id');
        $queryData->setOrder('A.name');

        if ($SearchData->getSeachString() !== '') {
            $queryData->setWhere('A.name LIKE ? OR C.name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($SearchData->getLimitStart());
        $queryData->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        $queryRes['count'] = $queryData->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @param int $id
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementViewCounter($id = null)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Account SET countView = (countView + 1) WHERE id = ? LIMIT 1');
        $queryData->addParam($id);

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @param $id
     * @return AccountExtData
     * @throws SPException
     */
    public function getDataForLink($id)
    {
        $query = /** @lang SQL */
            'SELECT A.name,
            A.login,
            A.pass,
            A.key,
            A.url,
            A.notes,
            C.name AS clientName,
            C2.name AS categoryName
            FROM Account A
            INNER JOIN Client C ON A.clientId = C.id
            INNER JOIN Category C2 ON A.categoryId = C2.id 
            WHERE A.id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setMapClassName(AccountExtData::class);
        $queryData->addParam($id);

        /** @var AccountExtData|array $queryRes */
        $queryRes = DbWrapper::getResults($queryData, $this->db);

        if ($queryRes === false) {
            throw new SPException(__u('No se pudieron obtener los datos de la cuenta'), SPException::ERROR);
        }

        if (is_array($queryRes) && count($queryRes) === 0) {
            throw new SPException(__u('La cuenta no existe'), SPException::ERROR);
        }

        return $queryRes;
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param AccountSearchFilter $accountSearchFilter
     * @return AccountSearchResponse
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter)
    {
        $queryFilterCommon = new QueryCondition();
        $queryFilterSelect = new QueryCondition();

        $searchText = $accountSearchFilter->getTxtSearch();

        if ($searchText !== null && $searchText !== '') {
            // Analizar la cadena de búsqueda por etiquetas especiales
            $stringFilter = $accountSearchFilter->getStringFilters();

            if (!empty($stringFilter)) {

                foreach ($stringFilter['values'] as $value) {
                    $queryFilterCommon->addFilter($stringFilter['query'], [$value]);
                }
            } else {
                $searchText = '%' . $searchText . '%';

                $queryFilterCommon->addFilter('A.name LIKE ? OR A.login LIKE ? OR A.url LIKE ? OR A.notes LIKE ?', [$searchText, $searchText, $searchText, $searchText]);
            }
        }

        if ($accountSearchFilter->getCategoryId() !== 0) {
            $queryFilterSelect->addFilter('A.categoryId = ?', [$accountSearchFilter->getCategoryId()]);
        }

        if ($accountSearchFilter->getClientId() !== 0) {
            $queryFilterSelect->addFilter('A.clientId = ?', [$accountSearchFilter->getClientId()]);
        }

        $tagsId = $accountSearchFilter->getTagsId();
        $numTags = count($tagsId);

        if ($numTags > 0) {
            $queryFilterSelect->addFilter('A.id IN (SELECT accountId FROM AccountToTag WHERE tagId IN (' . str_repeat('?,', $numTags - 1) . '?' . '))', $tagsId);
        }

        $where = [];

        if ($queryFilterCommon->hasFilters()) {
            $where[] = $queryFilterCommon->getFilters(QueryCondition::CONDITION_OR);
        }

        if ($queryFilterSelect->hasFilters()) {
            $where[] = $queryFilterSelect->getFilters();
        }

        $queryFilterUser = AccountUtil::getAccountFilterUser($this->session, $accountSearchFilter->getGlobalSearch());

        if ($queryFilterUser->hasFilters()) {
            $where[] = $queryFilterUser->getFilters();
        }

        $join = ['query' => [], 'param' => []];

        if ($accountSearchFilter->isSearchFavorites() === true) {
            $join['query'][] = 'INNER JOIN AccountToFavorite AF ON (AF.accountId = A.id AND AF.userId = ?)';
            $join['param'][] = $this->session->getUserData()->getId();
        }

        $queryData = new QueryData();
        $queryData->setWhere($where);
        $queryData->setParams(array_merge($join['param'], $queryFilterCommon->getParams(), $queryFilterSelect->getParams(), $queryFilterUser->getParams()));
        $queryData->setSelect('*');
        $queryData->setFrom('account_search_v A ' . implode(PHP_EOL, $join['query']));
        $queryData->setOrder($accountSearchFilter->getOrderString());

        if ($accountSearchFilter->getLimitCount() > 0) {
            $queryLimit = '?, ?';

            $queryData->addParam($accountSearchFilter->getLimitStart());
            $queryData->addParam($accountSearchFilter->getLimitCount());
            $queryData->setLimit($queryLimit);
        }

        $queryData->setMapClassName(AccountSearchVData::class);

        return new AccountSearchResponse($this->db->getFullRowCount($queryData), DbWrapper::getResultsArray($queryData, $this->db));
    }

    /**
     * @param QueryCondition $queryFilter
     * @return array
     */
    public function getForUser(QueryCondition $queryFilter)
    {
        $query = /** @lang SQL */
            'SELECT A.id, A.name, C.name AS clientName 
            FROM Account A
            LEFT JOIN Client C ON A.clientId = C.id 
            WHERE ' . $queryFilter->getFilters() . ' ORDER BY name';

        $queryData = new QueryData();
        $queryData->setMapClassName(ItemData::class);
        $queryData->setQuery($query);
        $queryData->setParams($queryFilter->getParams());

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * @param QueryCondition $queryFilter
     * @return array
     */
    public function getLinked(QueryCondition $queryFilter)
    {
        $query = /** @lang SQL */
            'SELECT A.id, A.name, C.name AS clientName 
            FROM Account A
            INNER JOIN Client C ON A.clientId = C.id 
            WHERE ' . $queryFilter->getFilters() . ' ORDER  BY name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($queryFilter->getParams());

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return array Con los datos de la clave
     */
    public function getAccountsPassData()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name, pass, `key` FROM Account WHERE BIT_LENGTH(pass) > 0');

        return DbWrapper::getResultsArray($queryData, $this->db);
    }
}