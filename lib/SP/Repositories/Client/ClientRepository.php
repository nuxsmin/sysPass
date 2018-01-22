<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Account\AccountUtil;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ClientData;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Mvc\Model\QueryFilter;
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
     * @return mixed
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('Cliente duplicado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO Client
            SET name = ?,
            description = ?,
            isGlobal = ?,
            `hash` = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getDescription());
        $Data->addParam($itemData->getIsGlobal());
        $Data->addParam($this->makeItemHash($itemData->getName()));
        $Data->setOnErrorMessage(__u('Error al crear el cliente'));

        DbWrapper::getQuery($Data, $this->db);

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
        $query = /** @lang SQL */
            'SELECT id FROM Client WHERE `hash` = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($itemData->getName()));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param ClientData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('Cliente duplicado'));
        }

        $query = /** @lang SQL */
            'UPDATE Client
            SET name = ?,
            description = ?,
            isGlobal = ?,
            `hash` = ?
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getDescription());
        $Data->addParam($itemData->getIsGlobal());
        $Data->addParam($this->makeItemHash($itemData->getName()));
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error al actualizar el cliente'));

        DbWrapper::getQuery($Data, $this->db);

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
        $query = /** @lang SQL */
            'SELECT id FROM Client WHERE `hash` = ? AND id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($itemData->getName()));
        $Data->addParam($itemData->getId());

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id, name, description, isGlobal FROM Client WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return array
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, name, description, isGlobal FROM Client ORDER BY name';

        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
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
            'SELECT id, name, description, isGlobal FROM Client WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        foreach ($ids as $id) {
            $this->delete($id);
        }
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return int
     * @throws SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM Client WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el cliente'));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows();
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
        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setSelect('id, name, description, isGlobal');
        $Data->setFrom('Client');
        $Data->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('name LIKE ? OR description LIKE ?');

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

    /**
     * Devolver los clientes visibles por el usuario
     *
     * @param QueryFilter $queryFilter
     * @return array
     */
    public function getAllForFilter(QueryFilter $queryFilter)
    {
        $query = /** @lang SQL */
            'SELECT C.id, C.name 
            FROM Account A
            RIGHT JOIN Client C ON clientId = C.id
            WHERE A.clientId IS NULL
            OR isGlobal = 1
            OR (' . $queryFilter->getFilters() . ')
            GROUP BY id
            ORDER BY name';

        $queryData = new QueryData();
        $queryData->setMapClassName(ItemData::class);
        $queryData->setQuery($query);
        $queryData->setParams($queryFilter->getParams());

        return DbWrapper::getResultsArray($queryData, $this->db);
    }
}