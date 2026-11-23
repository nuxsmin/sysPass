<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Auth\Models\AuthToken;
use SP\Domain\Auth\Ports\AuthTokenActionService;
use SP\Domain\Auth\Ports\AuthTokenService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\ItemTrait;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;

use function SP\__;

/**
 * A base class for all classes that creates viewieable actions
 */
abstract class AuthTokenViewBase extends ControllerBase
{
    use ItemTrait;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        WebControllerHelper                     $webControllerHelper,
        private readonly CustomFieldDataService $customFieldService,
        private readonly UserService            $userService,
        private readonly AuthTokenService       $authTokenService,
        private readonly AuthTokenActionService $authTokenAction,
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Sets view data for displaying auth token's data
     *
     * @param bool $isReadonly
     * @param int|null $authTokenId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    protected function setViewData(?int $authTokenId = null, bool $isReadonly = true): void
    {
        $this->view->addTemplate('auth_token', 'itemshow');

        $authToken = $authTokenId
            ? $this->authTokenService->getById($authTokenId)
                                     ->mutate(['token' => null, 'vault' => null, 'hash' => null])
            : new AuthToken();

        $this->view->assign('authToken', $authToken);

        $defaultTime = $this->configData->getAuthTokenDefaultTime();
        $maxTime = $this->configData->getAuthTokenMaxTime();
        $defaultDays = max(1, (int)round($defaultTime / 86400));
        $maxExpireDateValue = date('Y-m-d', time() + max(86400, $maxTime));
        $expireDate = $authToken->getExpireDate();

        if ($authTokenId === null) {
            $expireDateValue = '';
            $expireDateInitial = '__new__';
            $isNoExpiry = false;
            $expireDateDisplay = sprintf(__('%d days'), $defaultDays);
        } else {
            $expireDateValue = $expireDate !== null && $expireDate > 0 ? date('Y-m-d', $expireDate) : '';
            $expireDateInitial = $expireDate !== null && $expireDate > 0 ? date('Y-m-d', $expireDate) : 'never';
            $isNoExpiry = $expireDate === null;

            if ($expireDate === null) {
                $expireDateDisplay = __('Never');
            } elseif ($expireDate <= 0) {
                $expireDateDisplay = __('Invalid');
            } elseif ($expireDate <= time()) {
                $expireDateDisplay = date('Y-m-d H:i', $expireDate) . ' (' . __('Expired') . ')';
            } else {
                $expireDateDisplay = date('Y-m-d H:i', $expireDate);
            }
        }

        $this->view->assign('isAdminApp', (bool)$this->userDto?->isAdminApp);
        $this->view->assign('defaultTokenDays', $defaultDays);
        $this->view->assign('expireDateValue', $expireDateValue);
        $this->view->assign('expireDateInitial', $expireDateInitial);
        $this->view->assign('maxExpireDateValue', $maxExpireDateValue);
        $this->view->assign('isNoExpiry', $isNoExpiry);
        $this->view->assign('expireDateDisplay', $expireDateDisplay);

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

        $this->view->assign('isView', $isReadonly);

        if ($isReadonly) {
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
