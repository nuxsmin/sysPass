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
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Domain\User\Ports\UserToUserGroupRepository;
use SP\Domain\User\Ports\UserToUserGroupService;

/**
 * Class UserToUserGroup
 */
final class UserToUserGroup extends Service implements UserToUserGroupService
{

    public function __construct(
        Application                                $application,
        private readonly UserToUserGroupRepository $userToUserGroupRepository
    ) {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $id, array $users): int
    {
        return $this->userToUserGroupRepository->add($id, $users)->getLastId();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(int $id, array $users): int
    {
        if (count($users) === 0) {
            return $this->userToUserGroupRepository->delete($id)->getAffectedNumRows();
        }

        return $this->userToUserGroupRepository->update($id, $users)->getAffectedNumRows();
    }

    /**
     * @param int $id
     * @return array<int>
     */
    public function getUsersByGroupId(int $id): array
    {
        return array_map(
            static fn(UserToUserGroupModel $userToUserGroup) => $userToUserGroup->getUserId(),
            $this->userToUserGroupRepository
                ->getById($id)
                ->getDataAsArray(UserToUserGroupModel::class)
        );
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @param int $userId
     * @return array
     */
    public function getGroupsForUser(int $userId): array
    {
        return $this->userToUserGroupRepository
            ->getGroupsForUser($userId)
            ->getDataAsArray(UserToUserGroupModel::class);
    }
}
