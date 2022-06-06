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

namespace SP\Domain\User\In;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserToUserGroupRepository
 *
 * @package SP\Infrastructure\User\Repositories
 */
interface UserToUserGroupRepositoryInterface
{
    /**
     * Checks whether the user is included in the group
     *
     * @param  int  $groupId
     * @param  int  $userId
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkUserInGroup(int $groupId, int $userId): bool;

    /**
     * Returns the groups which the user belongs to
     *
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getGroupsForUser(int $userId): QueryResult;

    /**
     * Updates users from a group
     *
     * @param  int  $id
     * @param  array  $users
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(int $id, array $users): int;

    /**
     * Deletes users from a group
     *
     * @param $id int
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): int;

    /**
     * Adds users to a group
     *
     * @param  int  $groupId
     * @param  array  $users
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $groupId, array $users): int;

    /**
     * Returns users from a group by group Id
     *
     * @param $id int
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult;
}