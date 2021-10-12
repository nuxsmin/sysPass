<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\UserGroup;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserToUserGroupData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\UserGroup\UserToUserGroupRepository;
use SP\Services\Service;

/**
 * Class UserToUserGroupService
 *
 * @package SP\Services\UserGroup
 */
final class UserToUserGroupService extends Service
{
    protected ?UserToUserGroupRepository $userToUserGroupRepository = null;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getById(int $id): array
    {
        $result = $this->userToUserGroupRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(
                __u('Group not found'),
                SPException::INFO
            );
        }

        return $result->getDataAsArray();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(int $id, array $users): int
    {
        return $this->userToUserGroupRepository->add($id, $users);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(int $id, array $users): int
    {
        if (count($users) === 0) {
            return $this->userToUserGroupRepository->delete($id);
        }

        return $this->userToUserGroupRepository->update($id, $users);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersByGroupId(int $id): array
    {
        $usersId = [];

        /** @var UserToUserGroupData $userToUserGroupData */
        $userByGroup = $this->userToUserGroupRepository
            ->getById($id)
            ->getDataAsArray();

        foreach ($userByGroup as $userToUserGroupData) {
            $usersId[] = $userToUserGroupData->getUserId();
        }

        return $usersId;
    }

    /**
     * Checks whether the user is included in the group
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkUserInGroup(int $groupId, int $userId): bool
    {
        return $this->userToUserGroupRepository
            ->checkUserInGroup($groupId, $userId);
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getGroupsForUser(int $userId): array
    {
        return $this->userToUserGroupRepository
            ->getGroupsForUser($userId)
            ->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->userToUserGroupRepository = $this->dic->get(UserToUserGroupRepository::class);
    }
}