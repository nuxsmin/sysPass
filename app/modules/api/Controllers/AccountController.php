<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\InvalidClassException;
use SP\Modules\Api\Controllers\Help\AccountHelp;
use SP\Mvc\Model\QueryCondition;
use SP\Services\Account\AccountPresetService;
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
     * @var AccountPresetService
     */
    private $accountPresetService;
    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * viewAction
     */
    public function viewAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->accountService->incrementViewCounter($id);

            $this->eventDispatcher->notifyEvent('show.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account displayed'))
                    ->addDetail(__u('Name'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($accountDetails, $id));
        } catch (Exception $e) {
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

            $id = $this->apiService->getParamInt('id', true);
            $accountPassData = $this->accountService->getPasswordForId($id);
            $password = Crypt::decrypt($accountPassData->getPass(), $accountPassData->getKey(), $this->apiService->getMasterPass());

            $this->accountService->incrementDecryptCounter($id);

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('show.account.pass',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Password viewed'))
                    ->addDetail(__u('Name'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess(["password" => $password], $id));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * viewPassAction
     */
    public function editPassAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_EDIT_PASS);

            $accountRequest = new AccountRequest();
            $accountRequest->id = $this->apiService->getParamInt('id', true);
            $accountRequest->pass = $this->apiService->getParamString('pass', true);
            $accountRequest->passDateChange = $this->apiService->getParamInt('expireDate');
            $accountRequest->userEditId = $this->context->getUserData()->getId();

            $this->accountPresetService->checkPasswordPreset($accountRequest);

            $this->accountService->editPassword($accountRequest);

            $accountDetails = $this->accountService->getById($accountRequest->id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account.pass',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Password updated'))
                    ->addDetail(__u('Name'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail('ID', $accountDetails->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($accountDetails, $accountRequest->id, __('Password updated')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
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
            $accountRequest->isPrivate = $this->apiService->getParamInt('private');
            $accountRequest->isPrivateGroup = $this->apiService->getParamInt('privateGroup');
            $accountRequest->passDateChange = $this->apiService->getParamInt('expireDate');
            $accountRequest->parentId = $this->apiService->getParamInt('parentId');

            $userData = $this->context->getUserData();

            $accountRequest->userId = $this->apiService->getParamInt('userId', false, $userData->getId());
            $accountRequest->userGroupId = $this->apiService->getParamInt('userGroupId', false, $userData->getUserGroupId());

            $accountRequest->tags = array_map('intval', $this->apiService->getParamArray('tagsId', false, []));
            $accountRequest->pass = $this->apiService->getParamRaw('pass', true);

            $this->accountPresetService->checkPasswordPreset($accountRequest);

            $accountId = $this->accountService->create($accountRequest);

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent('create.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account created'))
                    ->addDetail(__u('Name'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail('ID', $accountDetails->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($accountDetails, $accountId, __('Account created')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * editAction
     */
    public function editAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_EDIT);

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

            $tagsId = array_map('intval', $this->apiService->getParamArray('tagsId', false, []));

            if (!empty($tagsId)) {
                $accountRequest->updateTags = true;
                $accountRequest->tags = $tagsId;
            }

            $this->accountService->update($accountRequest);

            $accountDetails = $this->accountService->getById($accountRequest->id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account updated'))
                    ->addDetail(__u('Name'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail('ID', $accountDetails->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($accountDetails, $accountRequest->id, __('Account updated')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
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

            $tagsId = array_map('intval', $this->apiService->getParamArray('tagsId', false, []));

            if (!empty($tagsId)) {
                $accountSearchFilter->setTagsId($tagsId);
            }

            $op = $this->apiService->getParamString('op');

            if ($op !== null) {
                switch ($op) {
                    case 'and':
                        $accountSearchFilter->setFilterOperator(QueryCondition::CONDITION_AND);
                        break;
                    case 'or':
                        $accountSearchFilter->setFilterOperator(QueryCondition::CONDITION_OR);
                        break;
                }
            }

            $accountSearchFilter->setLimitCount($this->apiService->getParamInt('count', false, 50));
            $accountSearchFilter->setSortOrder($this->apiService->getParamInt('order', false, AccountSearchFilter::SORT_DEFAULT));

            $this->returnResponse(
                ApiResponse::makeSuccess(
                    $this->accountService->getByFilter($accountSearchFilter)->getDataAsArray()));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * deleteAction
     */
    public function deleteAction()
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->accountService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account removed'))
                    ->addDetail(__u('Name'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($accountDetails, $id, __('Account removed')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws InvalidClassException
     */
    protected function initialize()
    {
        $this->accountService = $this->dic->get(AccountService::class);
        $this->accountPresetService = $this->dic->get(AccountPresetService::class);
        $this->apiService->setHelpClass(AccountHelp::class);
    }
}