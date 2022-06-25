<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Services\ApiResponse;

/**
 * Class ViewController
 */
final class ViewPassController extends AccountBase
{
    /**
     * viewPassAction
     */
    public function viewPassAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_VIEW_PASS);

            $id = $this->apiService->getParamInt('id', true);
            $accountPassData = $this->accountService->getPasswordForId($id);
            $password = Crypt::decrypt(
                $accountPassData->getPass(),
                $accountPassData->getKey(),
                $this->apiService->getMasterPass()
            );

            $this->accountService->incrementDecryptCounter($id);

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent(
                'show.account.pass',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Password viewed'))
                        ->addDetail(__u('Name'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                        ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess(["password" => $password], $id));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }
}