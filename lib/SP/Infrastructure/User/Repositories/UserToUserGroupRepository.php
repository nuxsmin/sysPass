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

namespace SP\Infrastructure\User\Repositories;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\UserToUserGroupData;
use SP\Domain\User\Ports\UserToUserGroupRepositoryInterface;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserToUserGroupRepository
 *
 * @package SP\Infrastructure\User\Repositories
 */
final class UserToUserGroupRepository extends Repository implements UserToUserGroupRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Checks whether the user is included in the group
     *
     * @param  int  $groupId
     * @param  int  $userId
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkUserInGroup(int $groupId, int $userId): bool
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = ? AND userId = ?');
        $queryData->setParams([$groupId, $userId]);

        return $this->db->doSelect($queryData)->getNumRows() === 1;
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getGroupsForUser(int $userId): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userGroupId FROM UserToUserGroup WHERE userId = ?');
        $queryData->addParam($userId);

        return $this->db->doSelect($queryData);
    }

    /**
     * Updates users from a group
     *
     * @param  int  $id
     * @param  array  $users
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(int $id, array $users): int
    {
        $this->delete($id);

        return $this->add($id, $users);
    }

    /**
     * Deletes users from a group
     *
     * @param $id int
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the group users'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Adds users to a group
     *
     * @param  int  $groupId
     * @param  array  $users
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $groupId, array $users): int
    {
        if (count($users) === 0) {
            return 0;
        }

        $query = /** @lang SQL */
            'INSERT INTO UserToUserGroup (userId, userGroupId) VALUES '.$this->buildParamsFromArray($users, '(?,?)');

        $queryData = new QueryData();
        $queryData->setQuery($query);

        foreach ($users as $user) {
            $queryData->addParam($user);
            $queryData->addParam($groupId);
        }

        $queryData->setOnErrorMessage(__u('Error while setting users in the group'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns users from a group by group Id
     *
     * @param $id int
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(UserToUserGroupData::class);
        $queryData->setQuery('SELECT userGroupId, userId FROM UserToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }
}
