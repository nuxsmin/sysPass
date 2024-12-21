<?php
declare(strict_types=1);
/**
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

namespace SP\Infrastructure\Tag\Repositories;

use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Tag\Models\Tag as TagModel;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class Tag
 *
 * @template T of TagModel
 */
final class Tag extends BaseRepository implements TagRepository
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param TagModel $tag
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(TagModel $tag): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($tag)) {
            throw new DuplicatedItemException(__u('Duplicated tag'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into(TagModel::TABLE)
            ->cols($tag->toArray(null, ['id', 'hash']))
            ->col('hash', $this->makeItemHash($tag->getName()));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the tag'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param TagModel $tag
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(TagModel $tag): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(TagModel::TABLE)
            ->where('hash = :hash')
            ->orWhere('name = :name')
            ->bindValues(
                [
                    'hash' => $tag->getHash(),
                    'name' => $tag->getName()
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param TagModel $tag
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(TagModel $tag): int
    {
        if ($this->checkDuplicatedOnUpdate($tag)) {
            throw new DuplicatedItemException(__u('Duplicated tag'));
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table(TagModel::TABLE)
            ->cols($tag->toArray(null, ['id', 'hash']))
            ->where('id = :id')
            ->limit(1)
            ->bindValues(
                [
                    'id' => $tag->getId(),
                    'hash' => $this->makeItemHash($tag->getName())
                ]
            );

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the tag'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param TagModel $tag
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnUpdate(TagModel $tag): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(TagModel::TABLE)
            ->where('(hash = :hash OR name = :name)')
            ->where('id <> :id')
            ->bindValues(
                [
                    'id' => $tag->getId(),
                    'hash' => $tag->getHash(),
                    'name' => $tag->getName(),
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $tagId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $tagId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(TagModel::TABLE)
            ->cols(TagModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $tagId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, TagModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param string $name
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(TagModel::TABLE)
            ->cols(TagModel::getCols())
            ->where('(name = :name OR hash = :hash)')
            ->bindValues(['name' => $name, 'hash' => $this->makeItemHash($name)])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, TagModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(TagModel::TABLE)
            ->cols(TagModel::getCols())
            ->orderBy(['name']);

        return $this->db->runQuery(QueryData::buildWithMapper($query, TagModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(TagModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $ids]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the tags'));

        return $this->db->runQuery($queryData);
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
            ->from(TagModel::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while removing the tag'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(TagModel::TABLE)
            ->cols(TagModel::getCols(['hash']))
            ->orderBy(['name'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['name' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(TagModel::class);

        return $this->db->runQuery($queryData, true);
    }
}
