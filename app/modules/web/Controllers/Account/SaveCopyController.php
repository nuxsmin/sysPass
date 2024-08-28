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

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__u;

/**
 * Class SaveCopyController
 */
final class SaveCopyController extends AccountSaveBase
{
    /**
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function saveCopyAction(): ActionResponse
    {
        $this->accountForm->validateFor(AclActionsInterface::ACCOUNT_CREATE);

        $accountId = $this->accountService->create($this->accountForm->getItemData());

        $this->eventDispatcher->notify(
            'create.account',
            new Event(
                $this,
                function () use ($accountId) {
                    $accountDetails = $this->accountService->getByIdEnriched($accountId);

                    return EventMessage::build(__u('Account created'))
                                       ->addDetail(__u('Account'), $accountDetails->getName())
                                       ->addDetail(__u('Client'), $accountDetails->getClientName());
                }
            )
        );

        $this->addCustomFieldsForItem(
            AclActionsInterface::ACCOUNT,
            $accountId,
            $this->request,
            $this->customFieldService
        );

        return ActionResponse::ok(
            __u('Account created'),
            [
                'itemId' => $accountId,
                'nextAction' => $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_EDIT),
            ]
        );
    }
}
