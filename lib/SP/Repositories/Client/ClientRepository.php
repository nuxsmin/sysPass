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

namespace SP\Repositories\Client;

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
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class ClientRepository
 *
 * @package SP\Repositories\Client
 */
class ClientRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param ClientData $itemData
     * @return int
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Cliente duplicado'), DuplicatedItemException::WARNING);
        }

        $query = /** @lang SQL */
            'INSERT INTO Client
            SET `name` = ?,
            description = ?,
            isGlobal = ?,
            `hash` = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getDescription());
        $queryData->addParam($itemData->getIsGlobal());
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));
        $queryData->setOnErrorMessage(__u('Error al crear el cliente'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param ClientData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Client WHERE `hash` = ? LIMIT 1');
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param ClientData $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws DuplicatedItemException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new DuplicatedItemException(__u('Cliente duplicado'), DuplicatedItemException::WARNING);
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
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getDescription());
        $queryData->addParam($itemData->getIsGlobal());
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));
        $queryData->addParam($itemData->getId());
        $queryData->setOnErrorMessage(__u('Error al actualizar el cliente'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param ClientData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Client WHERE (`hash` = ? OR `name` = ?) AND id <> ?');
        $queryData->addParam($this->makeItemHash($itemData->getName(), $this->db->getDbHandler()));
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getId());

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return ClientData
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, `name`, description, isGlobal FROM Client WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setMapClassName(ClientData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     * @return ClientData
     */
    public function getByName($name)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, `name`, description, isGlobal FROM Client WHERE `name` = ? OR `hash` = ? LIMIT 1');
        $queryData->addParam($name);
        $queryData->addParam($this->makeItemHash($name, $this->db->getDbHandler()));
        $queryData->setMapClassName(ClientData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return ClientData[]
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, `name`, description, isGlobal FROM Client ORDER BY `name`');
        $queryData->setMapClassName(ClientData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT id, `name`, description, isGlobal FROM Client WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($ids);
        $queryData->setMapClassName(ClientData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Client WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar los clientes'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Client WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar el cliente'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
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
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return ClientData[]
     */
    public function search(ItemSearchData $SearchData)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(ClientData::class);
        $queryData->setSelect('id, name, description, isGlobal');
        $queryData->setFrom('Client');
        $queryData->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ? OR description LIKE ?');

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
     * Devolver los clientes visibles por el usuario
     *
     * @param QueryCondition $queryFilter
     * @return ItemData[]
     * @throws QueryException
     */
    public function getAllForFilter(QueryCondition $queryFilter)
    {
        if (!$queryFilter->hasFilters()) {
            throw new QueryException(__u('Filtro incorrecto'));
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

        return DbWrapper::getResultsArray($queryData, $this->db);
    }
}