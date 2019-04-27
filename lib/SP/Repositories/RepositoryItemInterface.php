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

namespace SP\Repositories;

use SP\DataModel\ItemSearchData;

/**
 * Interface RepositoryItemInterface
 *
 * @package SP\Repositories
 */
interface RepositoryItemInterface
{
    /**
     * Creates an item
     *
     * @param mixed $itemData
     *
     * @return mixed
     */
    public function create($itemData);

    /**
     * Updates an item
     *
     * @param mixed $itemData
     *
     * @return mixed
     */
    public function update($itemData);

    /**
     * Deletes an item
     *
     * @param $id
     */
    public function delete($id);

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return mixed
     */
    public function getById($id);

    /**
     * Returns all the items
     *
     * @return array
     */
    public function getAll();

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return array
     */
    public function getByIdBatch(array $ids);

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return $this
     */
    public function deleteByIdBatch(array $ids);

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return bool
     */
    public function checkInUse($id);

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     *
     * @return bool
     */
    public function checkDuplicatedOnUpdate($itemData);

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     *
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData);

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     *
     * @return mixed
     */
    public function search(ItemSearchData $SearchData);
}