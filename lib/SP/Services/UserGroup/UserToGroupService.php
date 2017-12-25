<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\UserGroup;

use SP\DataModel\GroupUsersData;
use SP\Services\Service;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserToGroupService
 *
 * @package SP\Services\UserGroup
 */
class UserToGroupService extends Service
{
    use ServiceItemTrait;

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
            'SELECT usertogroup_groupId FROM usrToGroups WHERE usertogroup_groupId = ? AND usertogroup_userId = ?';

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
            'SELECT usertogroup_groupId AS groupId FROM usrToGroups WHERE usertogroup_userId = ?';

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
     * @return UserToGroupService
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
            'DELETE FROM usrToGroups WHERE usertogroup_groupId = ?';

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
     * @return UserToGroupService
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add($groupId, array $users)
    {
        $query = /** @lang SQL */
            'INSERT INTO usrToGroups (usertogroup_userId, usertogroup_groupId) VALUES ' . $this->getParamsFromArray($users, '(?,?)');

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
     * @return GroupUsersData[]
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT usertogroup_groupId, usertogroup_userId FROM usrToGroups WHERE usertogroup_groupId = ?';

        $Data = new QueryData();
        $Data->setMapClassName(GroupUsersData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data, $this->db);
    }
}