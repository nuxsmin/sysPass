<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Api\Controllers;

use SP\Account\AccountRequest;
use SP\Account\AccountSearchFilter;
use SP\Api\ApiResponse;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Services\Account\AccountService;

/**
 * Class AccountController
 *
 * @package api\Controllers
 */
class AccountController extends ControllerBase
{
    /**
     * @var AccountService
     */
    protected $accountService;

    /**
     * createAction
     */
    public function createAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_CREATE);

            $accountRequest = new AccountRequest();
            $accountRequest->name = $this->apiService->getParam('name', true);
            $accountRequest->clientId = $this->apiService->getParam('clientId', true);
            $accountRequest->categoryId = $this->apiService->getParam('categoryId', true);
            $accountRequest->login = $this->apiService->getParam('login');
            $accountRequest->url = $this->apiService->getParam('url');
            $accountRequest->notes = $this->apiService->getParam('notes');
            $accountRequest->otherUserEdit = 0;
            $accountRequest->otherUserGroupEdit = 0;
            $accountRequest->isPrivate = $this->apiService->getParam('private');
            $accountRequest->isPrivateGroup = $this->apiService->getParam('privateGroup');
            $accountRequest->passDateChange = $this->apiService->getParam('expireDate');
            $accountRequest->parentId = $this->apiService->getParam('parentId');
            $accountRequest->userGroupId = $this->context->getUserData()->getUserGroupId();
            $accountRequest->userId = $this->context->getUserData()->getId();

            $pass = $this->accountService->getPasswordEncrypted($this->apiService->getParam('pass', true), $this->apiService->getMasterPass());
            $accountRequest->pass = $pass['pass'];
            $accountRequest->key = $pass['key'];

            $accountId = $this->accountService->create($accountRequest);

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent('create.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cuenta creada'))
                    ->addDetail(__u('Cuenta'), $accountDetails->getName())
                    ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnResponse(new ApiResponse(__('Cuenta creada'), ApiResponse::RESULT_SUCCESS, $accountId));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * searchAction
     */
    public function searchAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_SEARCH);

            $accountSearchFilter = new AccountSearchFilter();
            $accountSearchFilter->setTxtSearch($this->apiService->getParam('text'));
            $accountSearchFilter->setCategoryId($this->apiService->getParam('categoryId'));
            $accountSearchFilter->setClientId($this->apiService->getParam('clientId'));
            $accountSearchFilter->setLimitCount($this->apiService->getParam('count', false, 50));
            $accountSearchFilter->setSortOrder($this->apiService->getParam('order', false, AccountSearchFilter::SORT_NAME));

            $this->returnResponse(new ApiResponse($this->accountService->getByFilter($accountSearchFilter)->getData()));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function initialize()
    {
        $this->accountService = $this->dic->get(AccountService::class);
    }
}