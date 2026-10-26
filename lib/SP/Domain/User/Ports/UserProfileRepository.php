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

namespace SP\Domain\User\Ports;

use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserProfile as UserProfileModel;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserProfileRepository
 *
 * @template T of UserProfileModel
 */
interface UserProfileRepository extends Repository
{
    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<UserProfileModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult;

    /**
     * Updates an item
     *
     * @param UserProfileModel $userProfile
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(UserProfileModel $userProfile): int;

    /**
     * Creates an item
     *
     * @param UserProfileModel $userProfile
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(UserProfileModel $userProfile): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<UserProfileModel>
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array<int> $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult<UserProfileModel>
     */
    public function getAll(): QueryResult;
}
