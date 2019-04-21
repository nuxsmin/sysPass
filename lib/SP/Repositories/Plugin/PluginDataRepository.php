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

namespace SP\Repositories\Plugin;

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class PluginDataRepository
 *
 * @package SP\Repositories\Plugin
 */
final class PluginDataRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param PluginDataModel $itemData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO PluginData SET `name` = ?, itemId = ?, `data` = ?, `key` = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getItemId(),
            $itemData->getData(),
            $itemData->getKey()
        ]);
        $queryData->setOnErrorMessage(__u('Error while adding plugin\'s data'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param PluginDataModel $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE PluginData
              SET `data` = ?, `key` = ?
              WHERE `name` = ? AND itemId = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getData(),
            $itemData->getKey(),
            $itemData->getName(),
            $itemData->getItemId()
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating plugin\'s data'));

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
        $queryData->setQuery('DELETE FROM PluginData WHERE `name` = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting plugin\'s data'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param string $name
     * @param int    $itemId
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByItemId($name, $itemId)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM PluginData WHERE `name` = ? AND itemId = ? LIMIT 1');
        $queryData->setParams([$name, $itemId]);
        $queryData->setOnErrorMessage(__u('Error while deleting plugin\'s data'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT itemId,
            `name`,
            `data`,
            `key`
            FROM PluginData
            WHERE `name` = ?';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginDataModel::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

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
        $query = /** @lang SQL */
            'SELECT itemId,
            `name`,
            `data`,
            `key` 
            FROM PluginData 
            ORDER BY `name`';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginDataModel::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return new QueryResult();
        }

        $query = /** @lang SQL */
            'SELECT itemId,
            `name`,
            `data`,
            `key`
            FROM PluginData 
            WHERE `name` IN (' . $this->getParamsFromArray($ids) . ')
            ORDER BY `name`';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginDataModel::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
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
        $queryData->setQuery('DELETE FROM PluginData WHERE `name` IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting plugin\'s data'));

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
     * @param ItemSearchData $SearchData
     *
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     * @param int    $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByItemId($name, $itemId)
    {
        $query = /** @lang SQL */
            'SELECT itemId,
            `name`,
            `data`,
            `key` 
            FROM PluginData
            WHERE `name` = ? AND itemId = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginDataModel::class);
        $queryData->setQuery($query);
        $queryData->setParams([$name, $itemId]);

        return $this->db->doSelect($queryData);
    }
}