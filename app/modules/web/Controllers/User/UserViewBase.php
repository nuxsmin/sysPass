<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\User;


use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\DataModel\UserData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class UserViewBase
 */
abstract class UserViewBase extends ControllerBase
{
    use ItemTrait;

    protected UserServiceInterface                          $userService;
    private UserGroupServiceInterface $userGroupService;
    private UserProfileServiceInterface                     $userProfileService;
    private CustomFieldServiceInterface $customFieldService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        UserServiceInterface $userService,
        UserGroupServiceInterface $userGroupService,
        UserProfileServiceInterface $userProfileService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
        $this->userProfileService = $userProfileService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param  int|null  $userId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    protected function setViewData(?int $userId = null): void
    {
        $this->view->addTemplate('user', 'itemshow');

        $user = $userId
            ? $this->userService->getById($userId)
            : new UserData();

        $this->view->assign('user', $user);
        $this->view->assign(
            'groups',
            SelectItemAdapter::factory($this->userGroupService->getAllBasic())->getItemsFromModel()
        );
        $this->view->assign(
            'profiles',
            SelectItemAdapter::factory($this->userProfileService->getAllBasic())->getItemsFromModel()
        );
        $this->view->assign('isUseSSO', $this->configData->isAuthBasicAutoLoginEnabled());
        $this->view->assign(
            'mailEnabled',
            $this->configData->isMailEnabled()
        );
        $this->view->assign(
            'nextAction',
            Acl::getActionRoute(AclActionsInterface::ACCESS_MANAGE)
        );

        if ($this->view->isView === true
            || ($this->configData->isDemoEnabled()
                && $user->getLogin() === 'demo')
        ) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');

            $this->view->assign(
                'usage',
                array_map(
                    static function ($value) {
                        switch ($value->ref) {
                            case 'Account':
                                $value->icon = 'description';
                                break;
                            case 'UserGroup':
                                $value->icon = 'group';
                                break;
                            case 'PublicLink':
                                $value->icon = 'link';
                                break;
                            default:
                                $value->icon = 'info_outline';
                        }

                        return $value;
                    },
                    $this->userService->getUsageForUser($userId)
                )
            );
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(AclActionsInterface::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(AclActionsInterface::USER, $userId, $this->customFieldService)
        );
    }
}
