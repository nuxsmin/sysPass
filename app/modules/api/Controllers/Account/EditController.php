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

use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use function SP\__;
use function SP\__u;

/**
 * Class EditController
 */
final class EditController extends AccountBase
{
    /**
     * editAction
     */
    #[Action(ResponseType::JSON_RPC)]
    public function editAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::ACCOUNT_EDIT);

        $accountRequest = $this->buildAccountRequest();

        $this->accountService->update($accountRequest->id, $accountRequest);

        $accountDetails = $this->accountService->getByIdEnriched($accountRequest->id)->getAccountVData();

        $this->eventDispatcher->notify(
            'edit.account',
            new Event(
                $this,
                EventMessage::build()
                    ->addDescription(__u('Account updated'))
                    ->addDetail(__u('Name'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail('ID', $accountDetails->getId())
            )
        );

        return ActionResponse::ok(__('Account updated'), $accountDetails, $accountRequest->id);
    }

    /**
     * @throws ServiceException
     */
    private function buildAccountRequest(): AccountUpdateDto
    {
        return AccountUpdateDto::fromArray(
            [
                'id'             => $this->apiService->getParamInt('id', true),
                'name'           => $this->apiService->getParamString('name', true),
                'clientId'       => $this->apiService->getParamInt('clientId', true),
                'categoryId'     => $this->apiService->getParamInt('categoryId', true),
                'login'          => $this->apiService->getParamString('login'),
                'url'            => $this->apiService->getParamString('url'),
                'notes'          => $this->apiService->getParamString('notes'),
                'isPrivate'      => (bool)$this->apiService->getParamInt('private'),
                'isPrivateGroup' => (bool)$this->apiService->getParamInt('privateGroup'),
                'passDateChange' => $this->apiService->getParamInt('expireDate'),
                'parentId'       => $this->apiService->getParamInt('parentId'),
                'userId'         => $this->apiService->getParamInt('userId', false),
                'userGroupId'    => $this->apiService->getParamInt('userGroupId', false),
                'userEditId'     => $this->context->getUserData()->id,
                'tags'           => array_map(
                    'intval',
                    $this->apiService->getParamArray('tagsId', false, [])
                ),
            ]
        );
    }
}
