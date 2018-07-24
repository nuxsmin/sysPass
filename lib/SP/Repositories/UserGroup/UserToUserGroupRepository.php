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

namespace SP\Repositories\UserGroup;

use SP\DataModel\UserToUserGroupData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;

/**
 * Class UserToUserGroupRepository
 *
 * @package SP\Repositories\UserGroup
 */
class UserToUserGroupRepository extends Repository
{
    use RepositoryItemTrait;

    /**
     * Checks whether the user is included in the group
     *
     * @param $groupId
     * @param $userId
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkUserInGroup($groupId, $userId)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = ? AND userId = ?');
        $queryData->setParams([$groupId, $userId]);

        return $this->db->doSelect($queryData)->getNumRows() === 1;
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @param $userId
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getGroupsForUser($userId)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userGroupId FROM UserToUserGroup WHERE userId = ?');
        $queryData->addParam($userId);

        return $this->db->doSelect($queryData);
    }

    /**
     * Updates users from a group
     *
     * @param int   $id
     * @param array $users
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($id, array $users)
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar los usuarios del grupo'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Adds users to a group
     *
     * @param int   $groupId
     * @param array $users
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add($groupId, array $users)
    {
        if (empty($users)) {
            return 0;
        }

        $query = /** @lang SQL */
            'INSERT INTO UserToUserGroup (userId, userGroupId) VALUES ' . $this->getParamsFromArray($users, '(?,?)');

        $queryData = new QueryData();
        $queryData->setQuery($query);

        foreach ($users as $user) {
            $queryData->addParam($user);
            $queryData->addParam($groupId);
        }

        $queryData->setOnErrorMessage(__u('Error al asignar los usuarios al grupo'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns users from a group by group Id
     *
     * @param $id int
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(UserToUserGroupData::class);
        $queryData->setQuery('SELECT userGroupId, userId FROM UserToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }
}