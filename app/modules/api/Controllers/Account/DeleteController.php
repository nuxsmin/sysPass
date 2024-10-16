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

namespace SP\Modules\Api\Controllers\Account;


use Exception;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Dtos\ApiResponse;
use SP\Domain\Core\Acl\AclActionsInterface;

/**
 * Class DeleteController
 */
final class DeleteController extends AccountBase
{
    /**
     * deleteAction
     */
    public function deleteAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::ACCOUNT_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $accountDetails = $this->accountService->getByIdEnriched($id)->getAccountVData();

            $this->accountService->delete($id);

            $this->eventDispatcher->notify(
                'delete.account',
                new Event(
                    $this,
                    EventMessage::build()
                        ->addDescription(__u('Account removed'))
                        ->addDetail(__u('Name'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                        ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($accountDetails, $id, __('Account removed')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }
}
