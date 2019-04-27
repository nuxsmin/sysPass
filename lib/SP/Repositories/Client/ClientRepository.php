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

namespace SP\Repositories\Client;

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ClientData;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Mvc\Model\QueryCondition;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class ClientRepository
 *
 * @package SP\Repositories\Client
 */
final class ClientRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param ClientData $itemData
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated client'), DuplicatedItemException::WARNING);
        }

        $query = /** @lang SQL */
            'INSERT INTO Client
            SET `name` = ?,
            description = ?,
            isGlobal = ?,
            `hash` = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getDescription(),
            $itemData->getIsGlobal(),
            $this->makeItemHash($itemData->getName(), $this->db->getDbHandler())
        ]);
        $queryData->setOnErrorMessage(__u('Error while creating the client'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param ClientData $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Client WHERE `hash` = ? LIMIT 1');
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));

        return $this->db->doQuery($queryData)->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param ClientData $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated client'), DuplicatedItemException::WARNING);
        }

        $query = /** @lang SQL */
            'UPDATE Client
            SET `name` = ?,
            description = ?,
            isGlobal = ?,
            `hash` = ?
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getDescription(),
            $itemData->getIsGlobal(),
            $this->makeItemHash($itemData->getName(), $this->db->getDbHandler()),
            $itemData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the client'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param ClientData $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Client WHERE (`hash` = ? OR `name` = ?) AND id <> ?');
        $queryData->setParams([
            $this->makeItemHash($itemData->getName(), $this->db->getDbHandler()),
            $itemData->getName(),
            $itemData->getId()
        ]);

        return $this->db->doQuery($queryData)->getNumRows() > 0;
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
        $queryData->setMapClassName(ClientData::class);
        $queryData->setQuery('SELECT id, `name`, description, isGlobal FROM Client WHERE id = ? LIMIT 1');
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getByName($name)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(ClientData::class);
        $queryData->setQuery('SELECT id, `name`, description, isGlobal FROM Client WHERE `name` = ? OR `hash` = ? LIMIT 1');
        $queryData->setParams([
            $name,
            $this->makeItemHash($name, $this->db->getDbHandler())
        ]);

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
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, `name`, description, isGlobal FROM Client ORDER BY `name`');
        $queryData->setMapClassName(ClientData::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return array
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT id, `name`, description, isGlobal FROM Client WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(ClientData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData)->getDataAsArray();
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
        $queryData->setQuery('DELETE FROM Client WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the clients'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Client WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the client'));

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
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(ClientData::class);
        $queryData->setSelect('id, name, description, isGlobal');
        $queryData->setFrom('Client');
        $queryData->setOrder('name');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ? OR description LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
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
     * Devolver los clientes visibles por el usuario
     *
     * @param QueryCondition $queryFilter
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllForFilter(QueryCondition $queryFilter)
    {
        if (!$queryFilter->hasFilters()) {
            throw new QueryException(__u('Wrong filter'));
        }

        $query = /** @lang SQL */
            'SELECT Client.id, Client.name 
            FROM Account
            RIGHT JOIN Client ON Account.clientId = Client.id
            WHERE Account.clientId IS NULL
            OR Client.isGlobal = 1
            OR ' . $queryFilter->getFilters() . '
            GROUP BY id
            ORDER BY Client.name';

        $queryData = new QueryData();
        $queryData->setMapClassName(ItemData::class);
        $queryData->setQuery($query);
        $queryData->setParams($queryFilter->getParams());

        return $this->db->doSelect($queryData);
    }
}