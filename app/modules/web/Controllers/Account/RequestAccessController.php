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

namespace SP\Modules\Web\Controllers\Account;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\Account\AccountRequestHelper;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class RequestAccessController
 */
final class RequestAccessController extends ControllerBase
{

    public function __construct(
        Application                           $application,
        WebControllerHelper                   $webControllerHelper,
        private readonly AccountService       $accountService,
        private readonly AccountRequestHelper $accountRequestHelper
    ) {
        parent::__construct($application, $webControllerHelper);
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     *
     * @param int $id Account's ID
     * @return ActionResponse
     * @throws UnauthorizedActionException
     * @throws UnauthorizedPageException
     * @throws ConstraintException
     * @throws QueryException
     * @throws UpdatedMasterPassException
     * @throws NoSuchItemException
     */
    #[Action(ResponseType::PLAIN_TEXT)]
    public function requestAccessAction(int $id): ActionResponse
    {
        $this->accountRequestHelper->initializeFor(AclActionsInterface::ACCOUNT_REQUEST);
        $this->accountRequestHelper->setIsView(true);
        $this->accountRequestHelper->setViewForRequest(
            new AccountEnrichedDto($this->accountService->getByIdEnriched($id))
        );

        $this->view->addTemplate('account-request');
        $this->view->assign('formRoute', 'account/saveRequest');

        $this->eventDispatcher->notify('show.account.request', new Event($this));

        if ($this->isAjax === false) {
            $this->upgradeView();
        }

        return ActionResponse::ok($this->render());
    }
}
