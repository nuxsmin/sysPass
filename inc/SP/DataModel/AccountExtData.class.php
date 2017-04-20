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

namespace SP\DataModel;

/**
 * Class AccountExtData
 *
 * @package SP\DataModel
 */
class AccountExtData extends AccountData
{
    /**
     * @var array Los Ids de los usuarios secundarios de la cuenta.
     */
    public $usersId = [];
    /**
     * @var array Los Ids de los grupos secundarios de la cuenta.
     */
    public $userGroupsId = [];
    /**
     * @var array
     */
    public $tags = [];
    /**
     * @var string
     */
    public $category_name = '';
    /**
     * @var string
     */
    public $customer_name = '';
    /**
     * @var string
     */
    public $usergroup_name = '';
    /**
     * @var string
     */
    public $user_name = '';
    /**
     * @var string
     */
    public $user_login = '';
    /**
     * @var string
     */
    public $user_editName = '';
    /**
     * @var string
     */
    public $user_editLogin = '';
    /**
     * @var string
     */
    public $publicLink_hash = '';

    /**
     * @return string
     */
    public function getUserEditName()
    {
        return $this->user_editName;
    }

    /**
     * @return string
     */
    public function getUserEditLogin()
    {
        return $this->user_editLogin;
    }

    /**
     * @return string
     */
    public function getPublicLinkHash()
    {
        return $this->publicLink_hash;
    }

    /**
     * @return array
     */
    public function getAccountUsersId()
    {
        return $this->usersId;
    }

    /**
     * @return array
     */
    public function getAccountUserGroupsId()
    {
        return $this->userGroupsId;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * @return string
     */
    public function getUsergroupName()
    {
        return $this->usergroup_name;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->user_login;
    }

    /**
     * @return array
     */
    public function getUsersId()
    {
        return $this->usersId;
    }

    /**
     * @param array $usersId
     */
    public function setUsersId(array $usersId)
    {
        $this->usersId = $usersId;
    }

    /**
     * @return array
     */
    public function getUserGroupsId()
    {
        return $this->userGroupsId;
    }

    /**
     * @param array $userGroupsId
     */
    public function setUserGroupsId(array $userGroupsId)
    {
        $this->userGroupsId = $userGroupsId;
    }
}