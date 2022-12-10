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
use League\Fractal\Resource\Item;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Api\Services\ApiResponse;
use SP\Util\Util;

/**
 * Class ViewController
 */
final class ViewController extends AccountBase
{
    /**
     * viewAction
     */
    public function viewAction(): void
    {

        try {
            $this->setupApi(ActionsInterface::ACCOUNT_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $customFields = Util::boolval($this->apiService->getParamString('customFields'));

            if ($customFields) {
                $this->apiService->requireMasterPass();
            }

            $accountDetails = $this->accountService->getByIdEnriched($id)->getAccountVData();

            $this->accountService->incrementViewCounter($id);

            $accountEnrichedDto = new AccountEnrichedDto($accountDetails);
            $accountEnrichedDto = $this->accountService->withUsers($accountEnrichedDto);
            $accountEnrichedDto = $this->accountService->withUserGroups($accountEnrichedDto);
            $accountEnrichedDto = $this->accountService->withTags($accountEnrichedDto);

            $this->eventDispatcher->notifyEvent(
                'show.account',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account displayed'))
                        ->addDetail(__u('Name'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                        ->addDetail('ID', $id)
                )
            );

            $out = $this->fractal->createData(new Item($accountEnrichedDto, $this->accountAdapter));

            if ($customFields) {
                $this->fractal->parseIncludes(['customFields']);
            }

            $this->returnResponse(ApiResponse::makeSuccess($out->toArray(), $id));
        } catch (Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }
}
