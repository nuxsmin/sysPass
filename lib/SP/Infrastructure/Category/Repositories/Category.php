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

namespace SP\Infrastructure\Category\Repositories;

use Exception;
use SP\Domain\Category\Models\Category as CategoryModel;
use SP\Domain\Category\Ports\CategoryRepository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class Category
 *
 * @template T of CategoryModel
 */
final class Category extends BaseRepository implements CategoryRepository
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param CategoryModel $category
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function create(CategoryModel $category): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($category)) {
            throw new DuplicatedItemException(__u('Duplicated category'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into(CategoryModel::TABLE)
            ->cols($category->toArray(null, ['id', 'hash']))
            ->col('hash', $this->makeItemHash($category->getName()));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the category'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param CategoryModel $category
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(CategoryModel $category): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(CategoryModel::TABLE)
            ->where('hash = :hash')
            ->orWhere('name = :name')
            ->bindValues(
                [
                    'hash' => $category->getHash(),
                    'name' => $category->getName()
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param CategoryModel $category
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(CategoryModel $category): int
    {
        if ($this->checkDuplicatedOnUpdate($category)) {
            throw new DuplicatedItemException(__u('Duplicated category name'));
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table(CategoryModel::TABLE)
            ->cols($category->toArray(null, ['id', 'hash']))
            ->where('id = :id')
            ->bindValues(
                [
                    'id' => $category->getId(),
                    'hash' => $this->makeItemHash($category->getName())
                ]
            );

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the category'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param CategoryModel $category
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnUpdate(CategoryModel $category): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(CategoryModel::TABLE)
            ->where('(hash = :hash OR name = :name)')
            ->where('id <> :id')
            ->bindValues(
                [
                    'id' => $category->getId(),
                    'hash' => $category->getHash(),
                    'name' => $category->getName(),
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $categoryId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $categoryId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(CategoryModel::TABLE)
            ->cols(CategoryModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $categoryId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, CategoryModel::class);

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
            ->from(CategoryModel::TABLE)
            ->cols(CategoryModel::getCols())
            ->where('(name = :name OR hash = :hash)')
            ->bindValues(['name' => $name, 'hash' => $this->makeItemHash($name)])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, CategoryModel::class);

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
            ->from(CategoryModel::TABLE)
            ->cols(CategoryModel::getCols());

        return $this->db->runQuery(QueryData::buildWithMapper($query, CategoryModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $categoryIds
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $categoryIds): QueryResult
    {
        if (count($categoryIds) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(CategoryModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $categoryIds]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the categories'));

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
            ->from(CategoryModel::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the category'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<T>
     * @throws Exception
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(CategoryModel::TABLE)
            ->cols(CategoryModel::getCols(['hash']))
            ->orderBy(['name'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name OR description LIKE :description');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['name' => $search, 'description' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(CategoryModel::class);

        return $this->db->runQuery($queryData, true);
    }
}
