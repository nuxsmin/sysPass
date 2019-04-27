<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
    public $categoryName = '';
    /**
     * @var string
     */
    public $clientName = '';
    /**
     * @var string
     */
    public $userGroupName = '';
    /**
     * @var string
     */
    public $userName = '';
    /**
     * @var string
     */
    public $userLogin = '';
    /**
     * @var string
     */
    public $userEditName = '';
    /**
     * @var string
     */
    public $userEditLogin = '';
    /**
     * @var string
     */
    public $publicLinkHash = '';

    /**
     * @return string
     */
    public function getUserEditName()
    {
        return $this->userEditName;
    }

    /**
     * @return string
     */
    public function getUserEditLogin()
    {
        return $this->userEditLogin;
    }

    /**
     * @return string
     */
    public function getPublicLinkHash()
    {
        return $this->publicLinkHash;
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
        return $this->categoryName;
    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @return string
     */
    public function getUserGroupName()
    {
        return $this->userGroupName;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->userLogin;
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