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
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\UserGroup\UserGroupRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;

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
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->userGroupRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return UserGroupData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
    {
        $result = $this->userGroupRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Group not found'), NoSuchItemException::INFO);
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
            throw new NoSuchItemException(__u('Group not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     *
     * @return int
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->userGroupRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the groups'), ServiceException::WARNING);
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
     * @throws ConstraintException
     * @throws QueryException
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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName($name)
    {
        $result = $this->userGroupRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Group not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns the users that are using the given group id
     *
     * @param $id int
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageByUsers($id)
    {
        return $this->userGroupRepository->getUsageByUsers($id)->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->userGroupRepository = $this->dic->get(UserGroupRepository::class);
        $this->userToUserGroupService = $this->dic->get(UserToUserGroupService::class);
    }
}