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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\ClientData;
use SP\DataModel\ItemSearchData;
use SP\Services\Api\ApiResponse;
use SP\Services\Client\ClientService;

/**
 * Class ClientController
 * @package SP\Modules\Api\Controllers
 */
class ClientController extends ControllerBase
{
    /**
     * @var ClientService
     */
    protected $clientService;

    /**
     * viewAction
     */
    public function viewAction()
    {
        try {
            $this->setupApi(ActionsInterface::CLIENT_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $client = $this->clientService->getById($id);

            $this->eventDispatcher->notifyEvent('show.client', new Event($this));

            $this->returnResponse(new ApiResponse($client));
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
            $this->setupApi(ActionsInterface::CLIENT_CREATE);

            $clientData = new ClientData();
            $clientData->setName($this->apiService->getParamString('name', true));
            $clientData->setDescription($this->apiService->getParamString('description'));
            $clientData->setIsGlobal($this->apiService->getParamInt('global'));

            $clientId = $this->clientService->create($clientData);

            $this->eventDispatcher->notifyEvent('create.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cliente creado'))
                    ->addDetail(__u('Cliente'), $clientData->getName()))
            );

            $this->returnResponse(new ApiResponse(__('Cliente creado'), ApiResponse::RESULT_SUCCESS, $clientId));
        } catch (\Exception $e) {
            $this->returnResponseException($e);

            processException($e);
        }
    }

    /**
     * editAction
     */
    public function editAction()
    {
        try {
            $this->setupApi(ActionsInterface::CLIENT_EDIT);

            $clientData = new ClientData();
            $clientData->setId($this->apiService->getParamInt('id', true));
            $clientData->setName($this->apiService->getParamString('name', true));
            $clientData->setDescription($this->apiService->getParamString('description'));
            $clientData->setIsGlobal($this->apiService->getParamInt('global'));

            $this->clientService->update($clientData);

            $this->eventDispatcher->notifyEvent('edit.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cliente actualizado'))
                    ->addDetail(__u('Cliente'), $clientData->getName()))
            );

            $this->returnResponse(new ApiResponse(__('Cliente actualizado'), ApiResponse::RESULT_SUCCESS, $clientData->getId()));
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
            $this->setupApi(ActionsInterface::CLIENT_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $clientData = $this->clientService->getById($id);

            $this->clientService->delete($id);

            $this->eventDispatcher->notifyEvent('edit.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cliente eliminado'))
                    ->addDetail(__u('Cliente'), $clientData->getName()))
            );

            $this->returnResponse(new ApiResponse(__('Cliente eliminado'), ApiResponse::RESULT_SUCCESS, $id));
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
            $this->setupApi(ActionsInterface::CLIENT_SEARCH);

            $itemSearchData = new ItemSearchData();
            $itemSearchData->setSeachString($this->apiService->getParamString('text'));
            $itemSearchData->setLimitCount($this->apiService->getParamInt('count', false, self::SEARCH_COUNT_ITEMS));

            $this->eventDispatcher->notifyEvent('search.client', new Event($this));

            $this->returnResponse(new ApiResponse($this->clientService->search($itemSearchData)));
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
        $this->clientService = $this->dic->get(ClientService::class);
    }
}