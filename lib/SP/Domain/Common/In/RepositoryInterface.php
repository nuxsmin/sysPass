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

namespace SP\Domain\Common\In;

use SP\DataModel\ItemSearchData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Interface RepositoryItemInterface
 *
 * @package SP\Domain\Common\In
 */
interface RepositoryInterface
{
    /**
     * Creates an item
     *
     * @param  mixed  $itemData
     *
     * @return mixed
     */
    public function create($itemData);

    /**
     * Updates an item
     *
     * @param  mixed  $itemData
     *
     * @return mixed
     */
    public function update($itemData);

    /**
     * Deletes an item
     *
     * @param  int  $id
     */
    public function delete(int $id);

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return mixed
     */
    public function getById(int $id);

    /**
     * Returns all the items
     */
    public function getAll();

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return QueryResult
     */
    public function getByIdBatch(array $ids): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return bool
     */
    public function checkInUse(int $id): bool;

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  mixed  $itemData
     *
     * @return bool
     */
    public function checkDuplicatedOnUpdate($itemData): bool;

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  mixed  $itemData
     *
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData): bool;

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData);
}