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

namespace SP\Domain\User\Ports;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserProfile as UserProfileModel;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserProfileService
 *
 * @package SP\Domain\Common\Services\UserProfile
 */
interface UserProfileService
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): UserProfileModel;

    /**
     * @return QueryResult<UserProfileModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void;

    /**
     * @param int[] $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(UserProfileModel $userProfile): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function update(UserProfileModel $userProfile): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersForProfile(int $id): array;

    /**
     * Get all items from the service's repository
     *
     * @return array<UserProfileModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;
}
