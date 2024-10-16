<?php
declare(strict_types=1);
/**
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
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserGroup as UserGroupModel;
use SP\Domain\User\Ports\UserGroupRepository;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserToUserGroupService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class UserGroup
 *
 * @template T of UserGroupModel
 */
final class UserGroup extends Service implements UserGroupService
{
    public function __construct(
        Application                             $application,
        private readonly UserGroupRepository    $userGroupRepository,
        private readonly UserToUserGroupService $userToUserGroupService,
    ) {
        parent::__construct($application);
    }

    /**
     * @param ItemSearchDto $itemSearchData
     * @return QueryResult<T>
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->userGroupRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): UserGroupModel
    {
        $result = $this->userGroupRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Group not found'));
        }

        return $result->getData()
                      ->mutate(['users' => $this->userToUserGroupService->getUsersByGroupId($id)]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void
    {
        if ($this->userGroupRepository->delete($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Group not found'));
        }
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
        $count = $this->userGroupRepository->deleteByIdBatch($ids)->getAffectedNumRows();

        if ($count !== count($ids)) {
            throw ServiceException::warning(__u('Error while deleting the groups'));
        }

        return $count;
    }

    /**
     * @throws ServiceException
     */
    public function create(UserGroupModel $userGroup): int
    {
        return $this->userGroupRepository->transactionAware(
            function () use ($userGroup) {
                $id = $this->userGroupRepository->create($userGroup)->getLastId();

                $users = $userGroup->getUsers();

                if ($users !== null) {
                    $this->userToUserGroupService->add($id, $users);
                }

                return $id;
            },
            $this
        );
    }

    /**
     * @throws ServiceException
     */
    public function update(UserGroupModel $userGroup): void
    {
        $this->userGroupRepository->transactionAware(
            function () use ($userGroup) {
                $this->userGroupRepository->update($userGroup);

                $users = $userGroup->getUsers();

                if ($users !== null) {
                    $this->userToUserGroupService->update($userGroup->getId(), $users);
                }
            },
            $this
        );
    }

    /**
     * Get all items from the service's repository
     *
     * @return array<T>
     */
    public function getAll(): array
    {
        return $this->userGroupRepository->getAll()->getDataAsArray(UserGroupModel::class);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     * @return UserGroupModel
     * @throws NoSuchItemException
     */
    public function getByName(string $name): UserGroupModel
    {
        $result = $this->userGroupRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Group not found'));
        }

        return $result->getData(UserGroupModel::class);
    }

    /**
     * Returns the users that are using the given group id
     *
     * @return array<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsage(int $id): array
    {
        return $this->userGroupRepository->getUsage($id)->getDataAsArray(UserGroupModel::class);
    }

    /**
     * Returns the items that are using the given group id
     *
     * @return array<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageByUsers(int $id): array
    {
        return $this->userGroupRepository->getUsageByUsers($id)->getDataAsArray(UserGroupModel::class);
    }
}
