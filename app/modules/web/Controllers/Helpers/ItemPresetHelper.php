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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemPreset\Password;
use SP\DataModel\ItemPreset\SessionTimeout;
use SP\DataModel\ItemPresetData;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;

/**
 * Class ItemPresetHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class ItemPresetHelper extends HelperBase
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
     * @throws NoSuchPropertyException
     */
    public function makeAccountPermissionView(ItemPresetData $itemPresetData)
    {
        $accountPermission = $itemPresetData->hydrate(AccountPermission::class, 'data') ?: new AccountPermission();

        $this->view->assign('typeTemplate', 'item_preset-permission');
        $this->view->assign('presetName', __('Permission Preset'));

        $this->view->assign('permission', $accountPermission);

        $this->view->assign('usersView', $this->users->getItemsFromModelSelected($accountPermission->getUsersView()));
        $this->view->assign('usersEdit', $this->users->getItemsFromModelSelected($accountPermission->getUsersEdit()));
        $this->view->assign('userGroupsView', $this->userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsView()));
        $this->view->assign('userGroupsEdit', $this->userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsEdit()));
    }

    /**
     * @param ItemPresetData $itemPresetData
     *
     * @throws NoSuchPropertyException
     */
    public function makeAccountPrivateView(ItemPresetData $itemPresetData)
    {
        $accountPrivate = $itemPresetData->hydrate(AccountPrivate::class, 'data') ?: new AccountPrivate();

        $this->view->assign('typeTemplate', 'item_preset-private');
        $this->view->assign('presetName', __('Private Account Preset'));

        $this->view->assign('private', $accountPrivate);
    }

    /**
     * @param ItemPresetData $itemPresetData
     *
     * @throws NoSuchPropertyException
     * @throws InvalidArgumentException
     */
    public function makeSessionTimeoutView(ItemPresetData $itemPresetData)
    {
        $sessionTimeout = $itemPresetData->hydrate(SessionTimeout::class, 'data') ?: new SessionTimeout($this->request->getClientAddress(), 3600);

        $this->view->assign('typeTemplate', 'item_preset-session_timeout');
        $this->view->assign('presetName', __('Session Timeout Preset'));

        $this->view->assign('sessionTimeout', $sessionTimeout);
    }

    /**
     * @param ItemPresetData $itemPresetData
     *
     * @throws NoSuchPropertyException
     */
    public function makeAccountPasswordView(ItemPresetData $itemPresetData)
    {
        $password = $itemPresetData->hydrate(Password::class, 'data') ?: new Password;

        $this->view->assign('typeTemplate', 'item_preset-password');
        $this->view->assign('presetName', __('Account Password Preset'));

        $this->view->assign('password', $password);

        $this->view->assign('expireTimeMultiplier', Password::EXPIRE_TIME_MULTIPLIER);
    }

    /**
     * @param ItemPresetData $itemPresetData
     */
    public function setCommon(ItemPresetData $itemPresetData)
    {
        $this->users = SelectItemAdapter::factory(UserService::getItemsBasic());
        $this->userGroups = SelectItemAdapter::factory(UserGroupService::getItemsBasic());
        $this->userProfiles = SelectItemAdapter::factory(UserProfileService::getItemsBasic());

        $this->view->assign('users', $this->users->getItemsFromModelSelected([$itemPresetData->getUserId()]));
        $this->view->assign('userGroups', $this->userGroups->getItemsFromModelSelected([$itemPresetData->getUserGroupId()]));
        $this->view->assign('userProfiles', $this->userProfiles->getItemsFromModelSelected([$itemPresetData->getUserProfileId()]));
    }
}