<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Core\Application;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemPreset\Password;
use SP\DataModel\ItemPreset\SessionTimeout;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\ItemPreset\Models\ItemPreset;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;

/**
 * Class ItemPresetHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class ItemPresetHelper extends HelperBase
{
    private ?SelectItemAdapter          $users      = null;
    private ?SelectItemAdapter          $userGroups = null;
    private UserServiceInterface        $userService;
    private UserGroupService $userGroupService;
    private UserProfileServiceInterface $userProfileService;

    public function __construct(
        Application          $application,
        TemplateInterface    $template,
        RequestInterface     $request,
        UserServiceInterface $userService,
        UserGroupService     $userGroupService,
        UserProfileServiceInterface $userProfileService

    ) {
        parent::__construct($application, $template, $request);

        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
        $this->userProfileService = $userProfileService;
    }

    /**
     * @throws NoSuchPropertyException
     */
    public function makeAccountPermissionView(ItemPreset $itemPresetData): void
    {
        $accountPermission = $itemPresetData->hydrate(AccountPermission::class) ?? new AccountPermission();

        $this->view->assign('typeTemplate', 'item_preset-permission');
        $this->view->assign('presetName', __('Permission Preset'));

        $this->view->assign('permission', $accountPermission);

        $this->view->assign(
            'usersView',
            $this->users->getItemsFromModelSelected($accountPermission->getUsersView())
        );
        $this->view->assign(
            'usersEdit',
            $this->users->getItemsFromModelSelected($accountPermission->getUsersEdit())
        );
        $this->view->assign(
            'userGroupsView',
            $this->userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsView())
        );
        $this->view->assign(
            'userGroupsEdit',
            $this->userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsEdit())
        );
    }

    /**
     * @throws NoSuchPropertyException
     */
    public function makeAccountPrivateView(ItemPreset $itemPresetData): void
    {
        $accountPrivate = $itemPresetData->hydrate(AccountPrivate::class) ?? new AccountPrivate();

        $this->view->assign('typeTemplate', 'item_preset-private');
        $this->view->assign('presetName', __('Private Account Preset'));

        $this->view->assign('private', $accountPrivate);
    }

    /**
     * @throws NoSuchPropertyException
     * @throws InvalidArgumentException
     */
    public function makeSessionTimeoutView(ItemPreset $itemPresetData): void
    {
        $sessionTimeout = $itemPresetData->hydrate(SessionTimeout::class)
                          ?? new SessionTimeout($this->request->getClientAddress(), 3600);

        $this->view->assign('typeTemplate', 'item_preset-session_timeout');
        $this->view->assign('presetName', __('Session Timeout Preset'));

        $this->view->assign('sessionTimeout', $sessionTimeout);
    }

    /**
     * @throws NoSuchPropertyException
     */
    public function makeAccountPasswordView(ItemPreset $itemPresetData): void
    {
        $password = $itemPresetData->hydrate(Password::class) ?? new Password();

        $this->view->assign('typeTemplate', 'item_preset-password');
        $this->view->assign('presetName', __('Account Password Preset'));

        $this->view->assign('password', $password);

        $this->view->assign('expireTimeMultiplier', Password::EXPIRE_TIME_MULTIPLIER);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function setCommon(ItemPreset $itemPresetData): void
    {
        $this->users = SelectItemAdapter::factory($this->userService->getAll());
        $this->userGroups = SelectItemAdapter::factory($this->userGroupService->getAll());
        $userProfiles = SelectItemAdapter::factory($this->userProfileService->getAll());

        $this->view->assign(
            'users',
            $this->users->getItemsFromModelSelected([$itemPresetData->getUserId()])
        );
        $this->view->assign(
            'userGroups',
            $this->userGroups->getItemsFromModelSelected([$itemPresetData->getUserGroupId()])
        );
        $this->view->assign(
            'userProfiles',
            $userProfiles->getItemsFromModelSelected([$itemPresetData->getUserProfileId()])
        );
    }
}
