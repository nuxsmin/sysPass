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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\InvalidClassException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Modules\Api\Controllers\Help\TagHelp;
use SP\Services\Api\ApiResponse;
use SP\Services\UserGroup\UserGroupService;

/**
 * Class UserGroupController
 *
 * @package SP\Modules\Api\Controllers
 */
final class UserGroupController extends ControllerBase
{
    /**
     * @var UserGroupService
     */
    private $userGroupService;

    /**
     * viewAction
     */
    public function viewAction()
    {
        try {
            $this->setupApi(ActionsInterface::GROUP_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $userGroupData = $this->userGroupService->getById($id);

            $this->eventDispatcher->notifyEvent('show.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Group viewed'))
                    ->addDetail(__u('Name'), $userGroupData->getName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($userGroupData, $id));
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
            $this->setupApi(ActionsInterface::GROUP_CREATE);

            $userGroupData = new UserGroupData();
            $userGroupData->setName($this->apiService->getParamString('name', true));
            $userGroupData->setDescription($this->apiService->getParamString('description'));
            $userGroupData->setUsers($this->apiService->getParamArray('usersId'));

            $id = $this->userGroupService->create($userGroupData);

            $this->eventDispatcher->notifyEvent('create.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Group added'))
                    ->addDetail(__u('Name'), $userGroupData->getName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($userGroupData, $id, __('Group added')));
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
            $this->setupApi(ActionsInterface::GROUP_EDIT);

            $userGroupData = new UserGroupData();
            $userGroupData->setId($this->apiService->getParamInt('id', true));
            $userGroupData->setName($this->apiService->getParamString('name', true));
            $userGroupData->setDescription($this->apiService->getParamString('description'));
            $userGroupData->setUsers($this->apiService->getParamArray('usersId'));

            $this->userGroupService->update($userGroupData);

            $this->eventDispatcher->notifyEvent('edit.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Group updated'))
                    ->addDetail(__u('Name'), $userGroupData->getName())
                    ->addDetail('ID', $userGroupData->getId())
                    ->addExtra('userGroupId', $userGroupData->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($userGroupData, $userGroupData->getId(), __('Group updated')));
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
            $this->setupApi(ActionsInterface::GROUP_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $userGroupData = $this->userGroupService->getById($id);

            $this->userGroupService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Group deleted'))
                    ->addDetail(__u('Name'), $userGroupData->getName())
                    ->addDetail('ID', $id)
                    ->addExtra('userGroupId', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($userGroupData, $id, __('Group deleted')));
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
            $this->setupApi(ActionsInterface::GROUP_SEARCH);

            $itemSearchData = new ItemSearchData();
            $itemSearchData->setSeachString($this->apiService->getParamString('text'));
            $itemSearchData->setLimitCount($this->apiService->getParamInt('count', false, self::SEARCH_COUNT_ITEMS));

            $this->eventDispatcher->notifyEvent('search.userGroup', new Event($this));

            $this->returnResponse(ApiResponse::makeSuccess($this->userGroupService->search($itemSearchData)->getDataAsArray()));
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
        $this->userGroupService = $this->dic->get(UserGroupService::class);
        $this->apiService->setHelpClass(TagHelp::class);
    }
}