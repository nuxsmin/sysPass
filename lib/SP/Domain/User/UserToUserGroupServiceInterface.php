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

/**
 * Class UserToUserGroupService
 *
 * @package SP\Domain\Common\Services\UserGroup
 */
interface UserToUserGroupServiceInterface
{
    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(int $id, array $users): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(int $id, array $users): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersByGroupId(int $id): array;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getById(int $id): array;

    /**
     * Checks whether the user is included in the group
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkUserInGroup(int $groupId, int $userId): bool;

    /**
     * Returns the groups which the user belongs to
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getGroupsForUser(int $userId): array;
}