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

namespace SP\Domain\User\Services;


use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\User\In\UserGroupRepositoryInterface;
use SP\Domain\User\UserGroupServiceInterface;
use SP\Domain\User\UserToUserGroupServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserGroupRepository;

/**
 * Class UserGroupService
 *
 * @package SP\Domain\Common\Services\UserGroup
 */
final class UserGroupService extends Service implements UserGroupServiceInterface
{
    use ServiceItemTrait;

    protected UserGroupRepository $userGroupRepository;
    protected UserToUserGroupServiceInterface $userToUserGroupService;
    private DatabaseInterface $database;

    public function __construct(
        Application $application,
        UserGroupRepositoryInterface $userGroupRepository,
        UserToUserGroupServiceInterface $userToUserGroupService,
        DatabaseInterface $database
    ) {
        parent::__construct($application);

        $this->userGroupRepository = $userGroupRepository;
        $this->userToUserGroupService = $userToUserGroupService;
        $this->database = $database;
    }

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
            throw new NoSuchItemException(__u('Group not found'), SPException::INFO);
        }

        $data = $result->getData();
        $data->setUsers($this->userToUserGroupService->getUsersByGroupId($id));

        return $data;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): UserGroupServiceInterface
    {
        $delete = $this->userGroupRepository->delete($id);

        if ($delete === 0) {
            throw new NoSuchItemException(__u('Group not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * @param  int[]  $ids
     *
     * @throws \SP\Domain\Common\Services\ServiceException
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
     * @throws \SP\Domain\Common\Services\ServiceException
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
            },
            $this->database
        );
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function update(UserGroupData $itemData): void
    {
        $this->transactionAware(
            function () use ($itemData) {
                $this->userGroupRepository->update($itemData);

                $users = $itemData->getUsers();

                if ($users !== null) {
                    $this->userToUserGroupService->update($itemData->getId(), $users);
                }
            },
            $this->database
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
        return $this->userGroupRepository->getAll()->getDataAsArray();
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
            throw new NoSuchItemException(__u('Group not found'), SPException::INFO);
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
        return $this->userGroupRepository->getUsage($id)->getDataAsArray();
    }

    /**
     * Returns the items that are using the given group id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageByUsers(int $id): array
    {
        return $this->userGroupRepository->getUsageByUsers($id)->getDataAsArray();
    }
}