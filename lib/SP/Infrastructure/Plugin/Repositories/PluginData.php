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

use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Models\PluginData as PluginDataModel;
use SP\Domain\Plugin\Ports\PluginDataRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class PluginData
 *
 * @template T of PluginDataModel
 */
final class PluginData extends BaseRepository implements PluginDataRepository
{
    use RepositoryItemTrait;

    public const TABLE = 'PluginData';

    /**
     * Creates an item
     *
     * @param PluginDataModel $pluginData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PluginDataModel $pluginData): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols($pluginData->toArray(null, ['id']));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while adding plugin\'s data'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param PluginDataModel $pluginData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PluginDataModel $pluginData): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols($pluginData->toArray(null, ['name', 'itemId']))
            ->where(
                'name = :name AND itemId = :itemId',
                ['name' => $pluginData->getName(), 'itemId' => $pluginData->getItemId()]
            )
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating plugin\'s data'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param string $name
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(string $name): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('name = :name', ['name' => $name])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting plugin\'s data'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Deletes an item
     *
     * @param string $name
     * @param int $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByItemId(string $name, int $itemId): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('name = :name AND itemId = :itemId', ['name' => $name, 'itemId' => $itemId])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting plugin\'s data'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     * @return QueryResult<T>
     */
    public function getByName(string $name): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginDataModel::getCols())
            ->where('name = :name')
            ->bindValues(['name' => $name])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, PluginDataModel::class);

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
            ->cols(PluginDataModel::getCols())
            ->orderBy(['name']);

        return $this->db->doSelect(QueryData::buildWithMapper($query, PluginDataModel::class));
    }

    /**
     * Returns all the items for given names
     *
     * @param string[] $names
     *
     * @return QueryResult<T>
     */
    public function getByNameBatch(array $names): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginDataModel::getCols())
            ->where('name IN (:name)', ['name' => $names])
            ->orderBy(['name']);

        $queryData = QueryData::buildWithMapper($query, PluginDataModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Deletes all the items for given names
     *
     * @param string[] $names
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByNameBatch(array $names): QueryResult
    {
        if (count($names) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('name IN (:name)', ['name' => $names]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting plugin\'s data'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param string $name
     * @param int $itemId
     *
     * @return QueryResult<T>
     */
    public function getByItemId(string $name, int $itemId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(PluginDataModel::getCols())
            ->where('name = :name AND itemId = :itemId', ['name' => $name, 'itemId' => $itemId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, PluginDataModel::class);

        return $this->db->doSelect($queryData);
    }
}
