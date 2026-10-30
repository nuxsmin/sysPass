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

namespace SP\Modules\Web\Controllers\User;


use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\UseCases\Dtos\GetUserCommand;
use SP\Domain\User\UseCases\GetUserUseCase;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\ItemTrait;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;

/**
 * Class UserViewBase
 */
abstract class UserViewBase extends ControllerBase
{
    use ItemTrait;

    private UserService $userService;
    private UserGroupService $userGroupService;
    private UserProfileService $userProfileService;
    private CustomFieldDataService $customFieldService;
    private GetUserUseCase $getUserUseCase;

    public function __construct(
        WebControllerHelper    $webControllerHelper,
        UserService            $userService,
        UserGroupService       $userGroupService,
        UserProfileService     $userProfileService,
        CustomFieldDataService $customFieldService,
        GetUserUseCase         $getUserUseCase
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();

        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
        $this->userProfileService = $userProfileService;
        $this->customFieldService = $customFieldService;
        $this->getUserUseCase = $getUserUseCase;
    }

    /**
     * Returns the user for the given id, or a new one when there is no id.
     *
     * @throws SPException
     */
    protected function getUser(?int $userId = null): User
    {
        return $userId
            ? $this->getUserUseCase->execute(new GetUserCommand($userId))->model
            : new User();
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
    protected function setViewData(?int $userId = null, bool $isView = false): void
    {
        $this->view->addTemplate('user', 'itemshow');

        $user = $this->getUser($userId);

        $this->view->assign('user', $user);
        $this->view->assign(
            'groups',
            SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel()
        );
        $this->view->assign(
            'profiles',
            SelectItemAdapter::factory($this->userProfileService->getAll())->getItemsFromModel()
        );
        $this->view->assign('isUseSSO', $this->configData->isAuthBasicAutoLoginEnabled());
        $this->view->assign(
            'mailEnabled',
            $this->configData->isMailEnabled()
        );
        $this->view->assign(
            'nextAction',
            $this->acl->getRouteFor(AclActionsInterface::ACCESS_MANAGE)
        );

        if ($isView
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