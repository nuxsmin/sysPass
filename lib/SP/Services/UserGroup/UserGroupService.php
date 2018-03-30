<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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


use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Repositories\UserGroup\UserGroupRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;

/**
 * Class UserGroupService
 *
 * @package SP\Services\UserGroup
 */
class UserGroupService extends Service
{
    use ServiceItemTrait;

    /**
     * @var UserGroupRepository
     */
    protected $userGroupRepository;
    /**
     * @var UserToUserGroupService
     */
    protected $userToUserGroupService;

    /**
     * @param ItemSearchData $itemSearchData
     * @return \SP\DataModel\ClientData[]
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->userGroupRepository->search($itemSearchData);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id)
    {
        return $this->userGroupRepository->getById($id);
    }

    /**
     * @param $id
     * @return $this
     * @throws SPException
     */
    public function delete($id)
    {
        if ($this->userGroupRepository->delete($id) === 0) {
            throw new ServiceException(__u('Grupo no encontrado'), ServiceException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     * @return int
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->userGroupRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error al eliminar los grupos'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param UserGroupData $itemData
     * @param array         $users
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData, array $users = [])
    {
        $userGroupId = $this->userGroupRepository->create($itemData);

        if (count($users) > 0) {
            $this->userToUserGroupService->add($userGroupId, $users);
        }

        return $userGroupId;
    }

    /**
     * @param UserGroupData $itemData
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $this->userGroupRepository->update($itemData);
        $this->userToUserGroupService->update($itemData->getId(), $itemData->getUsers());
    }

    /**
     * Get all items from the service's repository
     *
     * @return array
     */
    public function getAllBasic()
    {
        return $this->userGroupRepository->getAll();
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     * @return UserGroupData
     */
    public function getByName($name)
    {
        return $this->userGroupRepository->getByName($name);
    }

    /**
     * Returns the users that are using the given group id
     *
     * @param $id int
     * @return array
     */
    public function getUsage($id)
    {
        return $this->userGroupRepository->getUsage($id);
    }

    /**
     * Returns the items that are using the given group id
     *
     * @param $id int
     * @return array
     */
    public function getUsageByUsers($id)
    {
        return $this->userGroupRepository->getUsageByUsers($id);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->userGroupRepository = $this->dic->get(UserGroupRepository::class);
        $this->userToUserGroupService = $this->dic->get(UserToUserGroupService::class);
    }
}