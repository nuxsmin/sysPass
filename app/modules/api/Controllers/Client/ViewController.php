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
use League\Fractal\Resource\Item;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Api\Services\ApiResponse;
use SP\Util\Util;

/**
 * Class ViewController
 */
final class ViewController extends ClientBase
{
    /**
     * viewAction
     */
    public function viewAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::CLIENT_VIEW);

            $id = $this->apiService->getParamInt('id', true);

            $customFields = Util::boolval($this->apiService->getParamString('customFields'));

            $clientData = $this->clientService->getById($id);

            $this->eventDispatcher->notifyEvent('show.client', new Event($this));

            $this->eventDispatcher->notifyEvent(
                'show.client',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Client displayed'))
                        ->addDetail(__u('Name'), $clientData->getName())
                        ->addDetail('ID', $id)
                )
            );

            if ($customFields) {
                $this->apiService->requireMasterPass();
            }

            $out = $this->fractal->createData(new Item($clientData, $this->clientAdapter));

            if ($customFields) {
                $this->apiService->requireMasterPass();
                $this->fractal->parseIncludes(['customFields']);
            }

            $this->returnResponse(
                ApiResponse::makeSuccess($out->toArray(), $id)
            );
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }
}