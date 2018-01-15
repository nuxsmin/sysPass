<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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
    public static function checkUserInGroup($groupId, $userId)
    {
        $query = /** @lang SQL */
            'SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = ? AND userId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($groupId);
        $Data->addParam($userId);

        DbWrapper::getResults($Data);

        return ($Data->getQueryNumRows() === 1);
    }

    /**
     * Returns the groups which the user belongs to
     *
     * @param $userId
     * @return array
     */
    public static function getGroupsForUser($userId)
    {
        $query = /** @lang SQL */
            'SELECT userGroupId AS groupId FROM UserToUserGroup WHERE userId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userId);

        return DbWrapper::getResultsArray($Data);
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
        $query = /** @lang SQL */
            'DELETE FROM UserToUserGroup WHERE userGroupId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar los usuarios del grupo'));

        DbWrapper::getQuery($Data, $this->db);

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

        $Data = new QueryData();
        $Data->setQuery($query);

        foreach ($users as $user) {
            $Data->addParam($user);
            $Data->addParam($groupId);
        }

        $Data->setOnErrorMessage(__u('Error al asignar los usuarios al grupo'));

        DbWrapper::getQuery($Data, $this->db);

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
        $query = /** @lang SQL */
            'SELECT userGroupId, userId FROM UserToUserGroup WHERE userGroupId = ?';

        $Data = new QueryData();
        $Data->setMapClassName(UserToUserGroupData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data, $this->db);
    }
}