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
use SP\Domain\Account\Dtos\AccountHistoryViewDto;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHistoryHelper;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__;

/**
 * ViewHistoryController
 */
final class ViewHistoryController extends AccountControllerBase
{

    public function __construct(
        Application                            $application,
        WebControllerHelper                    $webControllerHelper,
        private readonly AccountHistoryService $accountHistoryService,
        private readonly AccountHistoryHelper  $accountHistoryHelper
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta en fecha concreta
     *
     * @param int $id Account's ID
     * @throws AccountPermissionException
     * @throws UnauthorizedActionException
     * @throws UnauthorizedPageException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws UpdatedMasterPassException
     * @throws NoSuchItemException
     */
    #[Action(ResponseType::PLAIN_TEXT)]
    public function viewHistoryAction(int $id): ActionResponse
    {
        $this->accountHistoryHelper->initializeFor(AclActionsInterface::ACCOUNT_HISTORY_VIEW);

        $this->accountHistoryHelper->setViewForAccount(
            AccountHistoryViewDto::fromArray($this->accountHistoryService->getById($id)->toArray())
        );

        $this->view->addTemplate('account-history');

        $this->view->assign(
            'title',
            [
                'class' => 'titleNormal',
                'name' => __('Account Details'),
                'icon' => 'access_time',
            ]
        );

        $this->view->assign('formRoute', 'account/saveRestore');

        $this->eventDispatcher->notify('show.account.history', new Event($this));

        if ($this->isAjax === false) {
            $this->upgradeView();
        }

        return ActionResponse::ok($this->render());
    }
}
