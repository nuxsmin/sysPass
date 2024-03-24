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

use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Models\Plugin as PluginModel;
use SP\Domain\Plugin\Ports\PluginRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class Plugin
 *
 * @template T of PluginModel
 */
final class Plugin extends BaseRepository implements PluginRepository
{
    use RepositoryItemTrait;

    public const TABLE = 'Plugin';

    /**
     * Creates an item
     *
     * @param PluginModel $plugin
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PluginModel $plugin): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols($plugin->toArray(null, ['id']));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while adding the plugin'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param PluginModel $plugin
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PluginModel $plugin): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols($plugin->toArray(null, ['id']))
            ->where('name = :name OR id = :id', ['name' => $plugin->getName(), 'id' => $plugin->getId()])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Devolver los plugins activados
     *
     * @return QueryResult<T>
     */
    public function getEnabled(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginModel::getCols())
            ->where('enabled = 1');

        $queryData = QueryData::buildWithMapper($query, PluginModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $pluginId
     *
     * @return QueryResult<T>
     */
    public function getById(int $pluginId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $pluginId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, PluginModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginModel::getCols())
            ->orderBy(['name']);

        return $this->db->doSelect(QueryData::buildWithMapper($query, PluginModel::class));
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $pluginsId
     *
     * @return QueryResult<T>
     */
    public function getByIdBatch(array $pluginsId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginModel::getCols())
            ->where('id IN (:ids)', ['ids' => $pluginsId])
            ->orderBy(['id']);

        $queryData = QueryData::buildWithMapper($query, PluginModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $pluginsId
     *
     * @return QueryResult
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $pluginsId): QueryResult
    {
        if (count($pluginsId) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('id IN (:ids) AND sticky = 0', ['ids' => $pluginsId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the plugins'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the plugin'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult<T>
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginModel::getCols())
            ->orderBy(['name'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['name' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(PluginModel::class);

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     *
     * @return QueryResult<T>
     */
    public function getByName(string $name): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginModel::getCols())
            ->where('name = :name', ['name' => $name])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, PluginModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param int $id
     * @param bool $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabled(int $id, bool $enabled): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols(['enabled' => $enabled])
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param string $name
     * @param bool $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabledByName(string $name, bool $enabled): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols(['enabled' => $enabled])
            ->where('name = :name', ['name' => $name])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param int $id
     * @param bool $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailable(int $id, bool $available): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols(['available' => $available, 'enabled' => 0])
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Cambiar el estado del plugin
     *
     * @param string $name
     * @param bool $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailableByName(string $name, bool $available): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols(['available' => $available, 'enabled' => 0])
            ->where('name = :name', ['name' => $name])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the plugin'));

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
    public function resetById(int $id): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols(['data' => null])
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the plugin'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }
}
