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

namespace SP\Services\UserGroup;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
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
    /**
     * @var UserToUserGroupRepository
     */
    protected $userToUserGroupRepository;

    /**
     * @param $id
     *
     * @return UserToUserGroupData[]
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        $result = $this->userToUserGroupRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Group not found'), NoSuchItemException::INFO);
        }

        return $result->getDataAsArray();
    }

    /**
     * @param       $id
     * @param array $users
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add($id, array $users)
    {
        return $this->userToUserGroupRepository->add($id, $users);
    }

    /**
     * @param int   $id
     * @param array $users
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($id, array $users)
    {
        if (count($users) === 0) {
            return $this->userToUserGroupRepository->delete($id);
        }

        return $this->userToUserGroupRepository->update($id, $users);
    }

    /**
     * @param $id
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersByGroupId($id)
    {
        $usersId = [];

        /** @var UserToUserGroupData $userToUserGroupData */
        foreach ($this->userToUserGroupRepository->getById($id)->getDataAsArray() as $userToUserGroupData) {
            $usersId[] = $userToUserGroupData->getUserId();
        }

        return $usersId;
    }

    /**
     * Checks whether the user is included in the group
     *
     * @param $groupId
     * @param $userId
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkUserInGroup($groupId, $userId)
    {
        return $this->userToUserGroupRepository->checkUserInGroup($groupId, $userId);
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @param $userId
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getGroupsForUser($userId)
    {
        return $this->userToUserGroupRepository->getGroupsForUser($userId)->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->userToUserGroupRepository = $this->dic->get(UserToUserGroupRepository::class);
    }
}