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
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHistoryHelper;
use SP\Modules\Web\Util\ErrorUtil;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * ViewHistoryController
 */
final class ViewHistoryController extends AccountControllerBase
{
    private AccountHistoryService $accountHistoryService;
    private AccountHistoryHelper  $accountHistoryHelper;

    public function __construct(
        Application           $application,
        WebControllerHelper   $webControllerHelper,
        AccountHistoryService $accountHistoryService,
        AccountHistoryHelper  $accountHistoryHelper
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountHistoryService = $accountHistoryService;
        $this->accountHistoryHelper = $accountHistoryHelper;
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta en fecha concreta
     *
     * @param  int  $id  Account's ID
     */
    public function viewHistoryAction(int $id): void
    {
        try {
            $accountHistoryData = $this->accountHistoryService->getById($id);

            $this->accountHistoryHelper->setView($accountHistoryData, AclActionsInterface::ACCOUNT_HISTORY_VIEW);

            $this->view->addTemplate('account-history');

            $this->view->assign(
                'title',
                [
                    'class' => 'titleNormal',
                    'name'  => __('Account Details'),
                    'icon'  => 'access_time',
                ]
            );

            $this->view->assign('formRoute', 'account/saveRestore');

            $this->eventDispatcher->notify('show.account.history', new Event($this));

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

            ErrorUtil::showExceptionInView($this->view, $e, 'account-history');
        }
    }

}
