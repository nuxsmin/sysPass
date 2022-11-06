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

namespace SP\Infrastructure\CustomField\Repositories;

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldTypeData;
use SP\DataModel\ItemSearchData;
use SP\Domain\CustomField\In\CustomFieldTypeRepositoryInterface;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldTypeRepository
 *
 * @package SP\Infrastructure\CustomField\Repositories
 */
final class CustomFieldTypeRepository extends Repository implements CustomFieldTypeRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param  CustomFieldTypeData  $itemData
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('INSERT INTO CustomFieldType SET `name` = ?, `text` = ?');
        $queryData->setParams([$itemData->getName(), $itemData->getText()]);
        $queryData->setOnErrorMessage(__u('Error while creating the field type'));

        return $this->db->doQuery($queryData)->getLastId();
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
     * Updates an item
     *
     * @param  CustomFieldTypeData  $itemData
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE CustomFieldType SET `name` = ?, `text` = ? WHERE id = ? LIMIT 1');
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getText(),
            $itemData->getId(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the field type'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
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
        $queryData->setMapClassName(CustomFieldTypeData::class);
        $queryData->setQuery('SELECT id, `name`, `text` FROM CustomFieldType WHERE id = ? LIMIT 1');
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
        $queryData = new QueryData();
        $queryData->setMapClassName(CustomFieldTypeData::class);
        $queryData->setQuery('SELECT id, `name`, `text` FROM CustomFieldType');

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return void
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        throw new RuntimeException('Not implemented');
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
        $queryData->setQuery('DELETE FROM CustomFieldType WHERE id IN ('.$this->buildParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the field type'));

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
    public function delete($id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM CustomFieldType WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the field type'));

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
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return void
     */
    public function search(ItemSearchData $itemSearchData): void
    {
        throw new RuntimeException('Not implemented');
    }
}