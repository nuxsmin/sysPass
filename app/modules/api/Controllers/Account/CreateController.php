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

namespace SP\Modules\Api\Controllers\Account;


use Exception;
use SP\Core\Acl\AclActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Api\Services\ApiResponse;

/**
 * Class CreateController
 */
final class CreateController extends AccountBase
{
    /**
     * createAction
     */
    public function createAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::ACCOUNT_CREATE);

            $accountRequest = $this->buildAccountRequest();

            $this->accountPresetService->checkPasswordPreset($accountRequest);

            $accountId = $this->accountService->create($accountRequest);

            $accountDetails = $this->accountService->getByIdEnriched($accountId)->getAccountVData();

            $this->eventDispatcher->notify(
                'create.account',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account created'))
                        ->addDetail(__u('Name'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                        ->addDetail('ID', $accountDetails->getId())
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($accountDetails, $accountId, __('Account created')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return \SP\Domain\Account\Dtos\AccountRequest
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function buildAccountRequest(): AccountRequest
    {
        $userData = $this->context->getUserData();

        $accountRequest = new AccountRequest();
        $accountRequest->name = $this->apiService->getParamString('name', true);
        $accountRequest->clientId = $this->apiService->getParamInt('clientId', true);
        $accountRequest->categoryId = $this->apiService->getParamInt('categoryId', true);
        $accountRequest->login = $this->apiService->getParamString('login');
        $accountRequest->url = $this->apiService->getParamString('url');
        $accountRequest->notes = $this->apiService->getParamString('notes');
        $accountRequest->isPrivate = $this->apiService->getParamInt('private');
        $accountRequest->isPrivateGroup = $this->apiService->getParamInt('privateGroup');
        $accountRequest->passDateChange = $this->apiService->getParamInt('expireDate');
        $accountRequest->parentId = $this->apiService->getParamInt('parentId');
        $accountRequest->userId = $this->apiService->getParamInt('userId', false, $userData->getId());
        $accountRequest->userGroupId =
            $this->apiService->getParamInt('userGroupId', false, $userData->getUserGroupId());
        $accountRequest->tags = array_map(
            'intval',
            $this->apiService->getParamArray('tagsId', false, [])
        );
        $accountRequest->pass = $this->apiService->getParamRaw('pass', true);

        return $accountRequest;
    }
}
