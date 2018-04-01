<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

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
     * @param $userId
     * @param $groupId
     * @return bool
     */
    public function checkUserInGroup($groupId, $userId)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = ? AND userId = ?');
        $queryData->addParam($groupId);
        $queryData->addParam($userId);

        DbWrapper::getResults($queryData, $this->db);

        return ($queryData->getQueryNumRows() === 1);
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @param $userId
     * @return array
     */
    public function getGroupsForUser($userId)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userGroupId FROM UserToUserGroup WHERE userId = ?');
        $queryData->addParam($userId);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Updates users from a group
     *
     * @param int   $id
     * @param array $users
     * @return UserToUserGroupRepository
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
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar los usuarios del grupo'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this;
    }

    /**
     * Adds users to a group
     *
     * @param int   $groupId
     * @param array $users
     * @return UserToUserGroupRepository
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add($groupId, array $users)
    {
        $query = /** @lang SQL */
            'INSERT INTO UserToUserGroup (userId, userGroupId) VALUES ' . $this->getParamsFromArray($users, '(?,?)');

        $queryData = new QueryData();
        $queryData->setQuery($query);

        foreach ($users as $user) {
            $queryData->addParam($user);
            $queryData->addParam($groupId);
        }

        $queryData->setOnErrorMessage(__u('Error al asignar los usuarios al grupo'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this;
    }

    /**
     * Returns users from a group by group Id
     *
     * @param $id int
     * @return UserToUserGroupData[]
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userGroupId, userId FROM UserToUserGroup WHERE userGroupId = ?');
        $queryData->addParam($id);
        $queryData->setMapClassName(UserToUserGroupData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }
}