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
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class PluginRepository
 *
 * @package SP\Repositories\Plugin
 */
final class PluginRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param PluginModel $itemData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO Plugin SET `name` = ?, `data` = ?, enabled = ?, available = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getData(),
            $itemData->getEnabled(),
            $itemData->getAvailable()
        ]);
        $queryData->setOnErrorMessage(__u('Error while adding the plugin'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param PluginModel $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData)
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
            $itemData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

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
    public function getAll()
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
            'SELECT id,
            `name`,
            enabled,
            available,
            versionLevel
            FROM Plugin 
            WHERE id IN (' . $this->getParamsFromArray($ids) . ')
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
     * @param array $ids
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Plugin WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the plugins'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
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
        $queryData->setMapClassName(PluginModel::class);
        $queryData->setSelect('id, name, enabled, available, versionLevel');
        $queryData->setFrom('Plugin');
        $queryData->setOrder('name');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
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
     * @param string $name
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName($name)
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
     * @param $id
     * @param $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabled($id, $enabled)
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
     * @param $name
     * @param $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabledByName($name, $enabled)
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
     * @param $id
     * @param $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailable($id, $available)
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
     * @param $name
     * @param $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailableByName($name, $available)
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
     * @param int $id Id del plugin
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function resetById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Plugin SET `data` = NULL WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Devolver los plugins activados
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getEnabled()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(ItemData::class);
        $queryData->setQuery('SELECT id, `name` FROM Plugin WHERE enabled = 1 ORDER BY id');

        return $this->db->doSelect($queryData);
    }
}