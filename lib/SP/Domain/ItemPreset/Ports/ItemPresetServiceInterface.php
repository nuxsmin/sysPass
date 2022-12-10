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

namespace SP\Domain\ItemPreset\Ports;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Models\ItemPreset;
use SP\Domain\ItemPreset\Services\ItemPresetRequest;
use SP\Domain\ItemPreset\Services\ItemPresetService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class ItemPresetService
 *
 * @package SP\Domain\Account\Services
 */
interface ItemPresetServiceInterface
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ItemPresetRequest $itemPresetRequest): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ItemPresetRequest $itemPresetRequest): int;

    /**
     * Deletes an item
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): ItemPresetService;

    /**
     * Returns the item for given id
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): ItemPreset;

    /**
     * Returns all the items
     *
     * @return ItemPreset[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForCurrentUser(string $type): ?ItemPreset;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUser(string $type, int $userId, int $userGroupId, int $userProfileId): ?ItemPreset;

    /**
     * @param  int[]  $ids
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function deleteByIdBatch(array $ids): int;
}
