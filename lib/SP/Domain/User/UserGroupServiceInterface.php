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

namespace SP\Domain\User;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserGroupService
 *
 * @package SP\Domain\Common\Services\UserGroup
 */
interface UserGroupServiceInterface
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): UserGroupData;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): UserGroupServiceInterface;

    /**
     * @param  int[]  $ids
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function create(UserGroupData $itemData): int;

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function update(UserGroupData $itemData): void;

    /**
     * Get all items from the service's repository
     *
     * @return UserGroupData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array;

    /**
     * Returns the item for given name
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): UserGroupData;

    /**
     * Returns the users that are using the given group id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsage(int $id): array;

    /**
     * Returns the items that are using the given group id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageByUsers(int $id): array;
}