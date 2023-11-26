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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Api\Services\ApiResponse;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;

/**
 * Class EditController
 */
final class EditController extends AccountBase
{
    /**
     * editAction
     */
    public function editAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::ACCOUNT_EDIT);

            $accountRequest = $this->buildAccountRequest();

            $this->accountService->update($accountRequest);

            $accountDetails = $this->accountService->getByIdEnriched($accountRequest->id)->getAccountVData();

            $this->eventDispatcher->notify(
                'edit.account',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account updated'))
                        ->addDetail(__u('Name'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                        ->addDetail('ID', $accountDetails->getId())
                )
            );

            $this->returnResponse(
                ApiResponse::makeSuccess($accountDetails, $accountRequest->id, __('Account updated'))
            );
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return AccountRequest
     * @throws ServiceException
     */
    private function buildAccountRequest(): AccountRequest
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = $this->apiService->getParamInt('id', true);
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
        $accountRequest->userId = $this->apiService->getParamInt('userId', false);
        $accountRequest->userGroupId = $this->apiService->getParamInt('userGroupId', false);
        $accountRequest->userEditId = $this->context->getUserData()->getId();

        $tagsId = array_map(
            'intval',
            $this->apiService->getParamArray('tagsId', false, [])
        );

        if (count($tagsId) !== 0) {
            $accountRequest->updateTags = true;
            $accountRequest->tags = $tagsId;
        }

        return $accountRequest;
    }
}
