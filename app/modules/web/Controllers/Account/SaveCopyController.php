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

use Exception;
use JsonException;
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Http\JsonResponse;

/**
 * Class SaveCopyController
 */
final class SaveCopyController extends AccountSaveBase
{
    /**
     * @return bool
     * @throws JsonException
     */
    public function saveCopyAction(): ?bool
    {
        try {
            $this->accountForm->validateFor(AclActionsInterface::ACCOUNT_CREATE);

            $accountId = $this->accountService->create($this->accountForm->getItemData());

            $accountDetails = $this->accountService->getByIdEnriched($accountId)->getAccountVData();

            $this->eventDispatcher->notify(
                'create.account',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Account created'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            $this->addCustomFieldsForItem(
                AclActionsInterface::ACCOUNT,
                $accountId,
                $this->request,
                $this->customFieldService
            );

            return $this->returnJsonResponseData(
                [
                    'itemId'     => $accountId,
                    'nextAction' => Acl::getActionRoute(AclActionsInterface::ACCOUNT_EDIT),
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Account created')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
