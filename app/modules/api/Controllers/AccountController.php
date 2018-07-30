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

use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Services\Account\AccountRequest;
use SP\Services\Account\AccountSearchFilter;
use SP\Services\Account\AccountService;
use SP\Services\Api\ApiResponse;

/**
 * Class AccountController
 *
 * @package SP\Modules\Api\Controllers
 */
final class AccountController extends ControllerBase
{
    /**
     * @var AccountService
     */
    protected $accountService;

    /**
     * viewAction
     */
    public function viewAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_VIEW);

            $accountId = $this->apiService->getParamInt('id', true);
            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->accountService->incrementViewCounter($accountId);

            $this->eventDispatcher->notifyEvent('show.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cuenta visualizada'))
                    ->addDetail(__u('Cuenta'), $accountDetails->getName())
                    ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnResponse(new ApiResponse($accountDetails));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * viewPassAction
     */
    public function viewPassAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_VIEW_PASS);

            $accountId = $this->apiService->getParamInt('id', true);
            $accountPassData = $this->accountService->getPasswordForId($accountId);
            $password = Crypt::decrypt($accountPassData->getPass(), $accountPassData->getKey(), $this->apiService->getMasterPass());

            $this->accountService->incrementDecryptCounter($accountId);

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent('show.account.pass',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Clave visualizada'))
                    ->addDetail(__u('Cuenta'), $accountDetails->getName())
                    ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnResponse(new ApiResponse(["itemId" => $accountId, "password" => $password]));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * createAction
     */
    public function createAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_CREATE);

            $accountRequest = new AccountRequest();
            $accountRequest->name = $this->apiService->getParamString('name', true);
            $accountRequest->clientId = $this->apiService->getParamInt('clientId', true);
            $accountRequest->categoryId = $this->apiService->getParamInt('categoryId', true);
            $accountRequest->login = $this->apiService->getParamString('login');
            $accountRequest->url = $this->apiService->getParamString('url');
            $accountRequest->notes = $this->apiService->getParamString('notes');
            $accountRequest->otherUserEdit = 0;
            $accountRequest->otherUserGroupEdit = 0;
            $accountRequest->isPrivate = $this->apiService->getParamInt('private');
            $accountRequest->isPrivateGroup = $this->apiService->getParamInt('privateGroup');
            $accountRequest->passDateChange = $this->apiService->getParamInt('expireDate');
            $accountRequest->parentId = $this->apiService->getParamInt('parentId');
            $accountRequest->userGroupId = $this->context->getUserData()->getUserGroupId();
            $accountRequest->userId = $this->context->getUserData()->getId();

            $pass = $this->accountService->getPasswordEncrypted($this->apiService->getParamRaw('pass', true), $this->apiService->getMasterPass());
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
            $accountSearchFilter->setCleanTxtSearch($this->apiService->getParamString('text'));
            $accountSearchFilter->setCategoryId($this->apiService->getParamInt('categoryId'));
            $accountSearchFilter->setClientId($this->apiService->getParamInt('clientId'));
            $accountSearchFilter->setLimitCount($this->apiService->getParamInt('count', false, 50));
            $accountSearchFilter->setSortOrder($this->apiService->getParamInt('order', false, AccountSearchFilter::SORT_DEFAULT));

            $this->returnResponse(new ApiResponse($this->accountService->getByFilter($accountSearchFilter)));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * deleteAction
     */
    public function deleteAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_DELETE);

            $accountId = $this->apiService->getParamInt('id', true);

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->accountService->delete($accountId);

            $this->eventDispatcher->notifyEvent('delete.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cuenta eliminada'))
                    ->addDetail(__u('Cuenta'), $accountDetails->getName())
                    ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnResponse(new ApiResponse(__u('Cuenta eliminada'), ApiResponse::RESULT_SUCCESS, $accountId));
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