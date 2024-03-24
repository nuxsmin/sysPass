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

namespace SP\Modules\Web\Controllers\AuthToken;


use SP\Core\Application;
use SP\Domain\Auth\Models\AuthToken;
use SP\Domain\Auth\Ports\AuthTokenActionInterface;
use SP\Domain\Auth\Ports\AuthTokenService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * A base class for all classes that creates viewieable actions
 */
abstract class AuthTokenViewBase extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                               $application,
        WebControllerHelper                       $webControllerHelper,
        private readonly CustomFieldDataService $customFieldService,
        private readonly UserServiceInterface     $userService,
        private readonly AuthTokenService         $authTokenService,
        private readonly AuthTokenActionInterface $authTokenAction,
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Sets view data for displaying auth token's data
     *
     * @param int|null $authTokenId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    protected function setViewData(?int $authTokenId = null): void
    {
        $this->view->addTemplate('auth_token', 'itemshow');

        $authToken = $authTokenId
            ? $this->authTokenService->getById($authTokenId)
            : new AuthToken();

        $this->view->assign('authToken', $authToken);

        $this->view->assign(
            'users',
            SelectItemAdapter::factory($this->userService->getAll())
                             ->getItemsFromModelSelected([$authToken->getUserId()])
        );
        $this->view->assign(
            'actions',
            SelectItemAdapter::factory($this->authTokenAction->getTokenActions())
                             ->getItemsFromArraySelected([$authToken->getActionId()])
        );

        $this->view->assign(
            'nextAction',
            $this->acl->getRouteFor(AclActionsInterface::ACCESS_MANAGE)
        );

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(AclActionsInterface::AUTHTOKEN, $authTokenId, $this->customFieldService)
        );
    }
}
