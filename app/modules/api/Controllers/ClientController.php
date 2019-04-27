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
use SP\DataModel\ClientData;
use SP\DataModel\ItemSearchData;
use SP\Modules\Api\Controllers\Help\ClientHelp;
use SP\Services\Api\ApiResponse;
use SP\Services\Client\ClientService;

/**
 * Class ClientController
 *
 * @package SP\Modules\Api\Controllers
 */
final class ClientController extends ControllerBase
{
    /**
     * @var ClientService
     */
    private $clientService;

    /**
     * viewAction
     */
    public function viewAction()
    {
        try {
            $this->setupApi(ActionsInterface::CLIENT_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $clientData = $this->clientService->getById($id);

            $this->eventDispatcher->notifyEvent('show.client', new Event($this));

            $this->eventDispatcher->notifyEvent('show.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Client displayed'))
                    ->addDetail(__u('Name'), $clientData->getName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($clientData, $id));
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
            $this->setupApi(ActionsInterface::CLIENT_CREATE);

            $clientData = new ClientData();
            $clientData->setName($this->apiService->getParamString('name', true));
            $clientData->setDescription($this->apiService->getParamString('description'));
            $clientData->setIsGlobal($this->apiService->getParamInt('global'));

            $id = $this->clientService->create($clientData);

            $this->eventDispatcher->notifyEvent('create.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Client added'))
                    ->addDetail(__u('Name'), $clientData->getName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($clientData, $id, __('Client added')));
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
            $this->setupApi(ActionsInterface::CLIENT_EDIT);

            $clientData = new ClientData();
            $clientData->setId($this->apiService->getParamInt('id', true));
            $clientData->setName($this->apiService->getParamString('name', true));
            $clientData->setDescription($this->apiService->getParamString('description'));
            $clientData->setIsGlobal($this->apiService->getParamInt('global'));

            $this->clientService->update($clientData);

            $this->eventDispatcher->notifyEvent('edit.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Client updated'))
                    ->addDetail(__u('Name'), $clientData->getName())
                    ->addDetail('ID', $clientData->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($clientData, $clientData->getId(), __('Client updated')));
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
            $this->setupApi(ActionsInterface::CLIENT_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $clientData = $this->clientService->getById($id);

            $this->clientService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Client deleted'))
                    ->addDetail(__u('Name'), $clientData->getName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($clientData, $id, __('Client deleted')));
        } catch (Exception $e) {
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

            $this->returnResponse(ApiResponse::makeSuccess($this->clientService->search($itemSearchData)->getDataAsArray()));
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
        $this->clientService = $this->dic->get(ClientService::class);
        $this->apiService->setHelpClass(ClientHelp::class);
    }
}