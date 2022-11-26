<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\UserGroup;


use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\DataModel\UserGroupData;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class UserGroupViewBase
 */
abstract class UserGroupViewBase extends ControllerBase
{
    use ItemTrait;

    private UserGroupServiceInterface   $userGroupService;
    private CustomFieldServiceInterface $customFieldService;
    private UserServiceInterface        $userService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        UserGroupServiceInterface $userGroupService,
        UserServiceInterface $userService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->userGroupService = $userGroupService;
        $this->userService = $userService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * Sets view data for displaying user group's data
     *
     * @param  int|null  $userGroupId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    protected function setViewData(?int $userGroupId = null): void
    {
        $this->view->addTemplate('user_group', 'itemshow');

        $userGroupData = $userGroupId
            ? $this->userGroupService->getById($userGroupId)
            : new UserGroupData();

        $this->view->assign('group', $userGroupData);

        $users = $userGroupData->getUsers() ?: [];

        $this->view->assign(
            'users',
            SelectItemAdapter::factory($this->userService->getAllBasic())->getItemsFromModelSelected($users)
        );
        $this->view->assign(
            'usedBy',
            $userGroupId
                ? $this->userGroupService->getUsageByUsers($userGroupId)
                : []
        );

        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(ActionsInterface::GROUP, $userGroupId, $this->customFieldService)
        );
    }
}
