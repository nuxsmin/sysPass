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

namespace SP\Modules\Web\Controllers\Account;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class SaveEditPassController
 */
final class SaveEditPassController extends AccountControllerBase
{
    private AccountForm $accountForm;

    public function __construct(
        Application                     $application,
        WebControllerHelper             $webControllerHelper,
        private readonly AccountService $accountService,
        AccountPresetService            $accountPresetService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->accountForm = new AccountForm($application, $this->request, $accountPresetService);
    }

    /**
     * Saves edit action
     *
     * @param int $id Account's ID
     *
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function saveEditPassAction(int $id): ActionResponse
    {
        $this->accountForm->validateFor(AclActionsInterface::ACCOUNT_EDIT_PASS, $id);

        $this->accountService->editPassword($id, $this->accountForm->getItemData());


        $this->eventDispatcher->notify(
            'edit.account.pass',
            new Event(
                $this,
                function () use ($id) {
                    $accountDetails = $this->accountService->getByIdEnriched($id);

                    return EventMessage::build(__u('Password updated'))
                                       ->addDetail(__u('Account'), $accountDetails->getName())
                                       ->addDetail(__u('Client'), $accountDetails->getClientName());
                }
            )
        );

        return ActionResponse::ok(
            __u('Password updated'),
            [
                'itemId' => $id,
                'nextAction' => $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW),
            ]
        );
    }
}
