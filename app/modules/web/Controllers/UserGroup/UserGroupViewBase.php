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

namespace SP\Modules\Web\Controllers\UserGroup;


use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
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

    private UserGroupService $userGroupService;
    private CustomFieldDataService $customFieldService;
    private UserService $userService;

    public function __construct(
        Application         $application,
        WebControllerHelper $webControllerHelper,
        UserGroupService    $userGroupService,
        UserService         $userService,
        CustomFieldDataService $customFieldService
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    protected function setViewData(?int $userGroupId = null): void
    {
        $this->view->addTemplate('user_group', 'itemshow');

        $userGroupData = $userGroupId
            ? $this->userGroupService->getById($userGroupId)
            : new UserGroup();

        $this->view->assign('group', $userGroupData);

        $users = $userGroupData->getUsers() ?: [];

        $this->view->assign(
            'users',
            SelectItemAdapter::factory($this->userService->getAll())->getItemsFromModelSelected($users)
        );
        $this->view->assign(
            'usedBy',
            $userGroupId
                ? $this->userGroupService->getUsageByUsers($userGroupId)
                : []
        );

        $this->view->assign('nextAction', Acl::getActionRoute(AclActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(AclActionsInterface::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(AclActionsInterface::GROUP, $userGroupId, $this->customFieldService)
        );
    }
}
