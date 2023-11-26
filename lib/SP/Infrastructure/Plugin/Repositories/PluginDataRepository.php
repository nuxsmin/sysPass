<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Infrastructure\Plugin\Repositories;

use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\PluginDataRepositoryInterface;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PluginDataRepository
 *
 * @package SP\Infrastructure\Plugin\Repositories
 */
final class PluginDataRepository extends Repository implements PluginDataRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param  PluginDataModel  $itemData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PluginDataModel $itemData): QueryResult
    {
        $query = /** @lang SQL */
            'INSERT INTO PluginData SET `name` = ?, itemId = ?, `data` = ?, `key` = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getItemId(),
            $itemData->getData(),
            $itemData->getKey(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while adding plugin\'s data'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param  PluginDataModel  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PluginDataModel $itemData): int
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
            $itemData->getItemId(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating plugin\'s data'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param  string  $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(string $id): int
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
     * @param  string  $name
     * @param  int  $itemId
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByItemId(string $name, int $itemId): int
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
     * @param  string  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(string $id): QueryResult
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
    public function getAll(): QueryResult
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
     * @param  string[]  $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
        }

        $query = /** @lang SQL */
            'SELECT itemId,
            `name`,
            `data`,
            `key`
            FROM PluginData 
            WHERE `name` IN ('.$this->buildParamsFromArray($ids).')
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
     * @param  string[]  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM PluginData WHERE `name` IN ('.$this->buildParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting plugin\'s data'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param  string  $name
     * @param  int  $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByItemId(string $name, int $itemId): QueryResult
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
