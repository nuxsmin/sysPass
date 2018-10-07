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
use SP\Repositories\NoSuchItemException;
use SP\Repositories\UserGroup\UserGroupRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;

/**
 * Class UserGroupService
 *
 * @package SP\Services\UserGroup
 */
final class UserGroupService extends Service
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
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->userGroupRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return UserGroupData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
    {
        $result = $this->userGroupRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Grupo no encontrado'), NoSuchItemException::INFO);
        }

        $data = $result->getData(UserGroupData::class);
        $data->setUsers($this->userToUserGroupService->getUsersByGroupId($id));

        return $data;
    }

    /**
     * @param $id
     *
     * @return $this
     * @throws SPException
     */
    public function delete($id)
    {
        if ($this->userGroupRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Grupo no encontrado'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     *
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
     *
     * @return int
     * @throws ServiceException
     */
    public function create($itemData)
    {
        return $this->transactionAware(function () use ($itemData) {
            $id = $this->userGroupRepository->create($itemData);

            $users = $itemData->getUsers();

            if ($users !== null) {
                $this->userToUserGroupService->add($id, $users);
            }

            return $id;
        });
    }

    /**
     * @param UserGroupData $itemData
     *
     * @throws ServiceException
     */
    public function update($itemData)
    {
        $this->transactionAware(function () use ($itemData) {
            $this->userGroupRepository->update($itemData);

            $users = $itemData->getUsers();

            if ($users !== null) {
                $this->userToUserGroupService->update($itemData->getId(), $users);
            }
        });
    }

    /**
     * Get all items from the service's repository
     *
     * @return UserGroupData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAllBasic()
    {
        return $this->userGroupRepository->getAll()->getDataAsArray();
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     *
     * @return UserGroupData
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByName($name)
    {
        $result = $this->userGroupRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Grupo no encontrado'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns the users that are using the given group id
     *
     * @param $id int
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUsage($id)
    {
        return $this->userGroupRepository->getUsage($id)->getDataAsArray();
    }

    /**
     * Returns the items that are using the given group id
     *
     * @param $id int
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUsageByUsers($id)
    {
        return $this->userGroupRepository->getUsageByUsers($id)->getDataAsArray();
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