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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\DataModel\AccountPermission;
use SP\DataModel\ItemPresetData;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;

/**
 * Class ItemPresetHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class ItemPresetHelper extends HelperBase
{
    /**
     * @var SelectItemAdapter
     */
    private $users;
    /**
     * @var SelectItemAdapter
     */
    private $userGroups;
    /**
     * @var SelectItemAdapter
     */
    private $userProfiles;

    /**
     * @param ItemPresetData $itemPresetData
     *
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     */
    public function makeAccountPermissionView(ItemPresetData $itemPresetData)
    {
        $accountPermission = $itemPresetData->hydrate(AccountPermission::class, 'data') ?: new AccountPermission();

        $this->view->assign('typeTemplate', 'item_preset-permission');

        $this->view->assign('permission', $accountPermission);

        $this->view->assign('users', $this->users->getItemsFromModelSelected([$itemPresetData->getUserId()]));
        $this->view->assign('usersView', $this->users->getItemsFromModelSelected($accountPermission->getUsersView()));
        $this->view->assign('usersEdit', $this->users->getItemsFromModelSelected($accountPermission->getUsersEdit()));

        $this->view->assign('userGroups', $this->userGroups->getItemsFromModelSelected([$itemPresetData->getUserGroupId()]));
        $this->view->assign('userGroupsView', $this->userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsView()));
        $this->view->assign('userGroupsEdit', $this->userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsEdit()));

        $this->view->assign('userProfiles', $this->userProfiles->getItemsFromModelSelected([$itemPresetData->getUserProfileId()]));
    }

    /**
     * makeDefaultPresetView
     */
    public function makeDefaultPresetView()
    {
        $this->view->assign('users', $this->users->getItemsFromModel());
        $this->view->assign('userGroups', $this->userGroups->getItemsFromModel());
        $this->view->assign('userProfiles', $this->userProfiles->getItemsFromModel());
    }

    protected function initialize()
    {
        $this->users = SelectItemAdapter::factory(UserService::getItemsBasic());
        $this->userGroups = SelectItemAdapter::factory(UserGroupService::getItemsBasic());
        $this->userProfiles = SelectItemAdapter::factory(UserGroupService::getItemsBasic());
    }
}