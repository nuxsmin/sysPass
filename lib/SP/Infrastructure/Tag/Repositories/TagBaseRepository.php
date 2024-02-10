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

namespace SP\Infrastructure\Tag\Repositories;

use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class TagRepository
 *
 * @package SP\Infrastructure\Common\Repositories\Tag
 */
final class TagBaseRepository extends BaseRepository implements TagRepository
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param Tag $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create($itemData): int
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated tag'));
        }

        $queryData = new QueryData();
        $queryData->setQuery('INSERT INTO Tag SET `name` = ?, `hash` = ?');
        $queryData->setParams([
            $itemData->getName(),
                                  $this->makeItemHash($itemData->getName()),
        ]);
        $queryData->setOnErrorMessage(__u('Error while creating the tag'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param Tag $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM Tag WHERE `name` = ? OR `hash` = ?');
        $queryData->setParams([
            $itemData->getName(),
                                  $this->makeItemHash($itemData->getName()),
        ]);

        return $this->db->doSelect($queryData)->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param Tag $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update($itemData): int
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated tag'));
        }

        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Tag SET `name` = ?, `hash` = ? WHERE id = ? LIMIT 1');
        $queryData->setParams([
            $itemData->getName(),
                                  $this->makeItemHash($itemData->getName()),
            $itemData->getId(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the tag'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param Tag $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT `hash` FROM Tag WHERE (`name` = ?  OR `hash` = ?) AND id <> ?');
        $queryData->setParams([
            $itemData->getName(),
                                  $this->makeItemHash($itemData->getName()),
            $itemData->getId(),
        ]);

        return $this->db->doSelect($queryData)->getNumRows() > 0;
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
        $queryData = new QueryData();
        $queryData->setMapClassName(Tag::class);
        $queryData->setQuery('SELECT id, `name` FROM Tag WHERE id = ?  ORDER BY  `name` LIMIT 1');
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param  string  $name
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(Tag::class);
        $queryData->setQuery('SELECT id, `name` FROM Tag WHERE `hash` = ? OR `name` = ? ORDER BY  `name` LIMIT 1');
        $queryData->setParams([$this->makeItemHash($name), $name]);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return Tag[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(Tag::class);
        $queryData->setQuery('SELECT id, `name`, `hash` FROM Tag ORDER BY `name`');

        return $this->db->doSelect($queryData)->getDataAsArray();
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
            'SELECT id, `name` FROM Tag WHERE id IN ('.$this->buildParamsFromArray($ids).')';

        $queryData = new QueryData();
        $queryData->setMapClassName(Tag::class);
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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Tag WHERE id IN ('.$this->buildParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while removing the tags'));

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
        $queryData->setQuery('DELETE FROM Tag WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while removing the tag'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkInUse(int $id): bool
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT tagId FROM AccountToTag WHERE tagId = ?');
        $queryData->addParam($id);

        return $this->db->doSelect($queryData)->getNumRows() > 0;
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
        $queryData->setSelect('id, name');
        $queryData->setFrom('Tag');
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
}
