<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
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

namespace SP\DataModel;

defined('APP_ROOT') || die();

/**
 * Class GroupUserData
 *
 * @package SP\DataModel
 */
class GroupUsersData extends DataModelBase
{
    /**
     * @var int
     */
    public $usertogroup_groupId = 0;
    /**
     * @var int
     */
    public $usertogroup_userId = 0;
    /**
     * @var array
     */
    public $users = [];

    /**
     * @return int
     */
    public function getUsertogroupGroupId()
    {
        return (int)$this->usertogroup_groupId;
    }

    /**
     * @param int $usertogroup_groupId
     */
    public function setUsertogroupGroupId($usertogroup_groupId)
    {
        $this->usertogroup_groupId = $usertogroup_groupId;
    }

    /**
     * @return int
     */
    public function getUsertogroupUserId()
    {
        return (int)$this->usertogroup_userId;
    }

    /**
     * @param int $usertogroup_userId
     */
    public function setUsertogroupUserId($usertogroup_userId)
    {
        $this->usertogroup_userId = $usertogroup_userId;
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param array $users
     */
    public function setUsers(array $users)
    {
        $this->users = $users;
    }

    /**
     * @param int $user
     */
    public function addUser($user)
    {
        $this->users[] = intval($user);
    }
}