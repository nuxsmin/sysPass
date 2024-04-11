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

namespace SP\Infrastructure\User\Repositories;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Domain\User\Ports\UserToUserGroupRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class UserToUserGroupRepository
 *
 * @template T of UserToUserGroupModel
 */
final class UserToUserGroup extends BaseRepository implements UserToUserGroupRepository
{
    /**
     * Checks whether the user is included in the group
     *
     * @param int $groupId
     * @param int $userId
     *
     * @return bool
     */
    public function checkUserInGroup(int $groupId, int $userId): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserToUserGroupModel::TABLE)
            ->cols(['userGroupId'])
            ->where('userGroupId = :userGroupId')
            ->where('userId = :userId')
            ->bindValues(['userGroupId' => $groupId, 'userId' => $userId]);

        return $this->db->runQuery(QueryData::build($query))->getNumRows() === 1;
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @param int $userId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getGroupsForUser(int $userId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserToUserGroupModel::TABLE)
            ->cols(UserToUserGroupModel::getCols())
            ->where('userId = :userId', ['userId' => $userId]);

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(UserToUserGroupModel::class));
    }

    /**
     * Updates users from a group
     *
     * @param int $id
     * @param array $users
     *
     * @return QueryResult
     * @throws ServiceException
     */
    public function update(int $id, array $users): QueryResult
    {
        if (count($users) === 0) {
            return new QueryResult();
        }

        return $this->transactionAware(function () use ($id, $users) {
            $this->delete($id);

            return $this->add($id, $users);
        }, $this);
    }

    /**
     * Deletes users from a group
     *
     * @param $id int
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(UserToUserGroupModel::TABLE)
            ->where('userGroupId = :userGroupId', ['userGroupId' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the group users'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Adds users to a group
     *
     * @param int $groupId
     * @param array $users
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $groupId, array $users): QueryResult
    {
        if (count($users) === 0) {
            return new QueryResult();
        }

        $rows = array_map(static fn(int $userId) => ['userGroupId' => $groupId, 'userId' => $userId], $users);

        $query = $this->queryFactory
            ->newInsert()
            ->into(UserToUserGroupModel::TABLE)
            ->addRows($rows);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while setting users in the group'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns users from a group by group id
     *
     * @param $id int
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserToUserGroupModel::getCols())
            ->from(UserToUserGroupModel::TABLE)
            ->where('userGroupId = :userGroupId', ['userGroupId' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserToUserGroupModel::class));
    }
}
