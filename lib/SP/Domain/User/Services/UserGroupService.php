<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupRepository;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserToUserGroupServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserGroupBaseRepository;

/**
 * Class UserGroupService
 *
 * @package SP\Domain\Common\Services\UserGroup
 */
final class UserGroupService extends Service implements UserGroupServiceInterface
{
    use ServiceItemTrait;

    protected UserGroupBaseRepository $userGroupRepository;
    protected UserToUserGroupServiceInterface $userToUserGroupService;
    private DatabaseInterface                 $database;

    public function __construct(
        Application         $application,
        UserGroupRepository $userGroupRepository,
        UserToUserGroupServiceInterface $userToUserGroupService,
        DatabaseInterface   $database
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
    public function getById(int $id): UserGroup
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
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
    public function create(UserGroup $itemData): int
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
     * @throws ServiceException
     */
    public function update(UserGroup $itemData): void
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
     * @return UserGroup[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
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
    public function getByName(string $name): UserGroup
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
