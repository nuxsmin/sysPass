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

namespace SP\Modules\Web\Controllers\AccountManager;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DeleteController extends ControllerBase
{
    use ItemTrait;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                             $application,
        WebControllerHelper                     $webControllerHelper,
        private readonly AccountService         $accountService,
        private readonly CustomFieldDataService $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Delete action
     *
     * @param int|null $id
     *
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function deleteAction(?int $id = null): ActionResponse
    {
        if ($id === null) {
            $ids = $this->getItemsIdFromRequest($this->request);
            $this->accountService->deleteByIdBatch($ids);

            $this->deleteCustomFieldsForItem(AclActionsInterface::ACCOUNT, $ids, $this->customFieldService);

            $this->eventDispatcher->notify(
                'delete.account.selection',
                new Event($this, EventMessage::build()->addDescription(__u('Accounts removed')))
            );

            return ActionResponse::ok(__u('Accounts removed'));
        }

        $accountView = $this->accountService->getByIdEnriched($id);

        $this->accountService->delete($id);

        $this->deleteCustomFieldsForItem(AclActionsInterface::ACCOUNT, $id, $this->customFieldService);

        $this->eventDispatcher->notify(
            'delete.account',
            new Event(
                $this,
                EventMessage::build(__u('Account removed'))
                            ->addDetail(__u('Account'), $accountView->getName())
                            ->addDetail(__u('Client'), $accountView->getClientName())
            )
        );

        return ActionResponse::ok(__u('Account removed'));
    }
}
