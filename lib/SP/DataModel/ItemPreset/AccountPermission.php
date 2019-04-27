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

namespace SP\DataModel\ItemPreset;

/**
 * Class AccountPermission
 *
 * @package SP\DataModel
 */
class AccountPermission
{
    /**
     * @var array
     */
    private $usersView = [];
    /**
     * @var array
     */
    private $usersEdit = [];
    /**
     * @var array
     */
    private $userGroupsView = [];
    /**
     * @var array
     */
    private $userGroupsEdit = [];

    /**
     * @return array
     */
    public function getUsersView(): array
    {
        return $this->usersView;
    }

    /**
     * @param array $usersView
     *
     * @return AccountPermission
     */
    public function setUsersView(array $usersView)
    {
        $this->usersView = $usersView;

        return $this;
    }

    /**
     * @return array
     */
    public function getUsersEdit(): array
    {
        return $this->usersEdit;
    }

    /**
     * @param array $usersEdit
     *
     * @return AccountPermission
     */
    public function setUsersEdit(array $usersEdit)
    {
        $this->usersEdit = $usersEdit;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserGroupsView(): array
    {
        return $this->userGroupsView;
    }

    /**
     * @param array $userGroupsView
     *
     * @return AccountPermission
     */
    public function setUserGroupsView(array $userGroupsView)
    {
        $this->userGroupsView = $userGroupsView;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserGroupsEdit(): array
    {
        return $this->userGroupsEdit;
    }

    /**
     * @param array $userGroupsEdit
     *
     * @return AccountPermission
     */
    public function setUserGroupsEdit(array $userGroupsEdit)
    {
        $this->userGroupsEdit = $userGroupsEdit;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasItems()
    {
        return count($this->usersView) > 0
            || count($this->usersEdit) > 0
            || count($this->userGroupsView) > 0
            || count($this->userGroupsEdit) > 0;
    }
}