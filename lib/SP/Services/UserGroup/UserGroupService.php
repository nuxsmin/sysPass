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

    protected ?UserGroupRepository $userGroupRepository = null;
    protected ?UserToUserGroupService $userToUserGroupService = null;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->userGroupRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): UserGroupData
    {
        $result = $this->userGroupRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(
                __u('Group not found'),
                SPException::INFO
            );
        }

        $data = $result->getData();
        $data->setUsers($this->userToUserGroupService->getUsersByGroupId($id));

        return $data;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function delete(int $id): UserGroupService
    {
        $delete = $this->userGroupRepository->delete($id);

        if ($delete === 0) {
            throw new NoSuchItemException(
                __u('Group not found'),
                SPException::INFO
            );
        }

        return $this;
    }

    /**
     * @param int[] $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->userGroupRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while deleting the groups'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * @throws ServiceException
     */
    public function create(UserGroupData $itemData): int
    {
        return $this->transactionAware(
            function () use ($itemData) {
                $id = $this->userGroupRepository->create($itemData);

                $users = $itemData->getUsers();

                if ($users !== null) {
                    $this->userToUserGroupService->add($id, $users);
                }

                return $id;
            }
        );
    }

    /**
     * @throws ServiceException
     */
    public function update(UserGroupData $itemData): void
    {
        $this->transactionAware(
            function () use ($itemData) {
                $this->userGroupRepository->update($itemData);

                $users = $itemData->getUsers();

                if ($users !== null) {
                    $this->userToUserGroupService->update(
                        $itemData->getId(),
                        $users
                    );
                }
            }
        );
    }

    /**
     * Get all items from the service's repository
     *
     * @return UserGroupData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->userGroupRepository
            ->getAll()
            ->getDataAsArray();
    }

    /**
     * Returns the item for given name
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): UserGroupData
    {
        $result = $this->userGroupRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(
                __u('Group not found'),
                SPException::INFO
            );
        }

        return $result->getData();
    }

    /**
     * Returns the users that are using the given group id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsage(int $id): array
    {
        return $this->userGroupRepository
            ->getUsage($id)
            ->getDataAsArray();
    }

    /**
     * Returns the items that are using the given group id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageByUsers(int $id): array
    {
        return $this->userGroupRepository
            ->getUsageByUsers($id)
            ->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->userGroupRepository = $this->dic->get(UserGroupRepository::class);
        $this->userToUserGroupService = $this->dic->get(UserToUserGroupService::class);
    }
}