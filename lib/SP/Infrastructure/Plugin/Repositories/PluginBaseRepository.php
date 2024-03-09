<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use RuntimeException;
use SP\DataModel\Item;
use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Ports\PluginRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PluginRepository
 *
 * @package SP\Infrastructure\Plugin\Repositories
 */
final class PluginBaseRepository extends BaseRepository implements PluginRepository
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param  PluginModel  $itemData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData): QueryResult
    {
        $query = /** @lang SQL */
            'INSERT INTO Plugin SET `name` = ?, `data` = ?, enabled = ?, available = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getData(),
            $itemData->getEnabled(),
            $itemData->getAvailable(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while adding the plugin'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Devolver los plugins activados
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getEnabled(): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(Item::class);
        $queryData->setQuery('SELECT id, `name` FROM Plugin WHERE enabled = 1 ORDER BY id');

        return $this->db->doSelect($queryData);
    }

    /**
     * Updates an item
     *
     * @param  PluginModel  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData): int
    {
        $query = /** @lang SQL */
            'UPDATE Plugin
              SET `name` = ?, 
              `data` = ?,
              enabled = ?,
              available = ?,
              versionLevel = ?
              WHERE `name` = ? OR id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getData(),
            $itemData->getEnabled(),
            $itemData->getAvailable(),
            $itemData->getVersionLevel(),
            $itemData->getName(),
            $itemData->getId(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            `name`,
            `data`,
            enabled,
            available,
            versionLevel
            FROM Plugin 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginModel::class);
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
            'SELECT id,
            `name`,
            enabled,
            available,
            versionLevel
            FROM Plugin 
            ORDER BY `name`';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginModel::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
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
            'SELECT id,
            `name`,
            enabled,
            available,
            versionLevel
            FROM Plugin 
            WHERE id IN ('.$this->buildParamsFromArray($ids).')
            ORDER BY id';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginModel::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Plugin WHERE id IN ('.$this->buildParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the plugins'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Plugin WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return void
     */
    public function checkInUse(int $id): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  mixed  $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  mixed  $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(PluginModel::class);
        $queryData->setSelect('id, name, enabled, available, versionLevel');
        $queryData->setFrom('Plugin');
        $queryData->setOrder('name');

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere('name LIKE ?');

            $search = '%'.$itemSearchData->getSeachString().'%';
            $queryData->addParam($search);
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param  string  $name
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            `name`,
            `data`,
            enabled,
            available,
            versionLevel
            FROM Plugin 
            WHERE `name` = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(PluginModel::class);
        $queryData->setQuery($query);
        $queryData->addParam($name);

        return $this->db->doSelect($queryData);
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param  int  $id
     * @param  bool  $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabled(int $id, bool $enabled): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET enabled = ? WHERE id = ? LIMIT 1');
        $queryData->setParams([(int)$enabled, $id]);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param  string  $name
     * @param  bool  $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabledByName(string $name, bool $enabled): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET enabled = ? WHERE name = ? LIMIT 1');
        $queryData->setParams([(int)$enabled, $name]);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param  int  $id
     * @param  bool  $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailable(int $id, bool $available): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET available = ?, enabled = 0 WHERE id = ? LIMIT 1');
        $queryData->setParams([(int)$available, $id]);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param  string  $name
     * @param  bool  $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailableByName(string $name, bool $available): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET available = ?, enabled = 0 WHERE `name` = ? LIMIT 1');
        $queryData->setParams([(int)$available, $name]);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Restablecer los datos de un plugin
     *
     * @param  int  $id  Id del plugin
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function resetById(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET `data` = NULL WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }
}
