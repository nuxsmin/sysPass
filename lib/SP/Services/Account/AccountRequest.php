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

namespace SP\Services\Account;

/**
 * Class AccountRequest
 *
 * @package SP\Account
 */
final class AccountRequest
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var int
     */
    public $clientId;
    /**
     * @var int
     */
    public $categoryId;
    /**
     * @var string
     */
    public $login;
    /**
     * @var string
     */
    public $url;
    /**
     * @var string
     */
    public $notes;
    /**
     * @var int
     */
    public $userId;
    /**
     * @var int
     */
    public $userGroupId;
    /**
     * @var int
     */
    public $userEditId;
    /**
     * @var int
     */
    public $otherUserEdit;
    /**
     * @var int
     */
    public $otherUserGroupEdit;
    /**
     * @var string
     */
    public $pass;
    /**
     * @var string
     */
    public $key;
    /**
     * @var int
     */
    public $isPrivate;
    /**
     * @var int
     */
    public $isPrivateGroup;
    /**
     * @var int
     */
    public $passDateChange;
    /**
     * @var int
     */
    public $parentId;
    /**
     * @var array
     */
    public $usersView;
    /**
     * @var array
     */
    public $usersEdit;
    /**
     * @var array
     */
    public $userGroupsView;
    /**
     * @var array
     */
    public $userGroupsEdit;
    /**
     * @var array
     */
    public $tags;
    /**
     * @var bool
     */
    public $changeOwner = false;
    /**
     * @var bool
     */
    public $changeUserGroup = false;
    /**
     * @var bool
     */
    public $changePermissions = false;
    /**
     * @var bool
     */
    public $updateTags = false;
}