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

namespace SP\Domain\ItemPreset\Ports;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class ItemPresetService
 *
 * @template T of ItemPresetModel
 */
interface ItemPresetService
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ItemPresetModel $itemPreset): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ItemPresetModel $itemPreset): int;

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): ItemPresetService;

    /**
     * Returns the item for given id
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): ItemPresetModel;

    /**
     * Returns all the items
     *
     * @return array<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * Searches for items by a given filter
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForCurrentUser(string $type): ?ItemPresetModel;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUser(string $type, int $userId, int $userGroupId, int $userProfileId): ?ItemPresetModel;

    /**
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int;
}
