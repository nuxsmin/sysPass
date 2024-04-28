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

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\Account\AccountRequestHelper;
use SP\Modules\Web\Util\ErrorUtil;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class RequestAccessController
 */
final class RequestAccessController extends ControllerBase
{
    private AccountRequestHelper $accountRequestHelper;
    private AccountService       $accountService;

    public function __construct(
        Application          $application,
        WebControllerHelper  $webControllerHelper,
        AccountService       $accountService,
        AccountRequestHelper $accountRequestHelper
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->accountRequestHelper = $accountRequestHelper;
        $this->accountService = $accountService;
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     *
     * @param  int  $id  Account's ID
     *
     */
    public function requestAccessAction(int $id): void
    {
        try {
            $this->accountRequestHelper->setIsView(true);
            $this->accountRequestHelper->setViewForRequest(
                $this->accountService->getByIdEnriched($id),
                AclActionsInterface::ACCOUNT_REQUEST
            );

            $this->view->addTemplate('account-request');
            $this->view->assign('formRoute', 'account/saveRequest');

            $this->eventDispatcher->notify('show.account.request', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            if ($this->isAjax === false && !$this->view->isUpgraded()) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account-request');
        }
    }
}
