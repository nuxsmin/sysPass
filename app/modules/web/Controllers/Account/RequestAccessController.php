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

namespace SP\Modules\Web\Controllers\Account;

use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Presenters\Helpers\Account\AccountRequestHelper;

/**
 * Class RequestAccessController
 */
final class RequestAccessController extends AccountControllerBase
{

    public function __construct(
        WebControllerHelper                   $webControllerHelper,
        private readonly AccountService       $accountService,
        private readonly AccountRequestHelper $accountRequestHelper
    ) {
        parent::__construct($webControllerHelper);
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     *
     * @param int $id Account's ID
     * @return ActionResponse
     * @throws AccountPermissionException
     * @throws UnauthorizedActionException
     * @throws UnauthorizedPageException
     * @throws ConstraintException
     * @throws QueryException
     * @throws UpdatedMasterPassException
     */
    #[Action(ResponseType::PLAIN_TEXT)]
    public function requestAccessAction(int $id): ActionResponse
    {
        $this->accountRequestHelper->initializeFor(AclActionsInterface::ACCOUNT_REQUEST);

        if (!$this->accountService->isVisible($id, true)) {
            throw new AccountPermissionException(SPException::INFO);
        }

        try {
            $accountView = $this->accountService->getByIdEnriched($id);
        } catch (NoSuchItemException) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $this->accountRequestHelper->setIsView(true);
        $this->accountRequestHelper->setViewForRequest(new AccountEnrichedDto($accountView));

        $this->view->addTemplate('account-request');
        $this->view->assign('formRoute', 'account/saveRequest');

        $this->eventDispatcher->notify('show.account.request', new Event($this));

        if ($this->isAjax === false) {
            $this->upgradeView();
        }

        return ActionResponse::ok($this->render());
    }
}
