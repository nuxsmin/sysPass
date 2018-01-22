<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Traits\InjectableTrait;
use SP\Repositories\UserGroup\UserToUserGroupRepository;
use SP\Services\ServiceItemTrait;

/**
 * Class UserToUserGroupService
 *
 * @package SP\Services\UserGroup
 */
class UserToUserGroupService
{
    use InjectableTrait;

    /**
     * @var UserToUserGroupRepository
     */
    protected $userToUserGroupRepository;

    /**
     * UserGroup constructor.
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param UserToUserGroupRepository $userToUserGroupRepository
     */
    public function inject(UserToUserGroupRepository $userToUserGroupRepository)
    {
        $this->userToUserGroupRepository = $userToUserGroupRepository;
    }

    /**
     * @param $id
     * @return \SP\DataModel\UserToUserGroupData[]
     */
    public function getById($id)
    {
        return $this->userToUserGroupRepository->getById($id);
    }

    /**
     * @param       $id
     * @param array $users
     * @return UserToUserGroupRepository
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add($id, array $users)
    {
        return $this->userToUserGroupRepository->add($id, $users);
    }

    /**
     * @param       $id
     * @param array $users
     * @return UserToUserGroupRepository
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($id, array $users)
    {
        return $this->userToUserGroupRepository->update($id, $users);
    }

    /**
     * @param $id
     * @return array
     */
    public function getUsersByGroupId($id)
    {
        $usersId = [];

        foreach ($this->userToUserGroupRepository->getById($id) as $userToUserGroupData) {
            $usersId[] = $userToUserGroupData->getUserId();
        }

        return $usersId;
    }
}