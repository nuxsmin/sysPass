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

namespace SP\Modules\Api\Controllers\Client;


use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\ClientData;
use SP\Domain\Api\Services\ApiResponse;

/**
 * Class EditController
 */
final class EditController extends ClientBase
{
    /**
     * editAction
     */
    public function editAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::CLIENT_EDIT);

            $clientData = $this->buildClientData();

            $this->clientService->update($clientData);

            $this->eventDispatcher->notifyEvent(
                'edit.client',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Client updated'))
                        ->addDetail(__u('Name'), $clientData->getName())
                        ->addDetail('ID', $clientData->getId())
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($clientData, $clientData->getId(), __('Client updated')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return \SP\DataModel\ClientData
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function buildClientData(): ClientData
    {
        $clientData = new ClientData();
        $clientData->setId($this->apiService->getParamInt('id', true));
        $clientData->setName($this->apiService->getParamString('name', true));
        $clientData->setDescription($this->apiService->getParamString('description'));
        $clientData->setIsGlobal($this->apiService->getParamInt('global'));

        return $clientData;
    }
}