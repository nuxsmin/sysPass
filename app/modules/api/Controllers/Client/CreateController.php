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

namespace SP\Modules\Api\Controllers\Client;

use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Client\Models\Client;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use function SP\__;
use function SP\__u;

/**
 * Class CreateController
 */
final class CreateController extends ClientBase
{
    /**
     * createAction
     */
    #[Action(ResponseType::JSON_RPC)]
    public function createAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::CLIENT_CREATE);

        $clientData = $this->buildClientData();

        $id = $this->clientService->create($clientData);

        $clientData = $clientData->mutate(['id' => $id]);

        $this->eventDispatcher->notify(
            'create.client',
            new Event(
                $this,
                EventMessage::build()
                    ->addDescription(__u('Client added'))
                    ->addDetail(__u('Name'), $clientData->getName())
                    ->addDetail('ID', $id)
            )
        );

        return ActionResponse::ok(__('Client added'), $clientData, $id);
    }

    /**
     * @return Client
     * @throws ServiceException
     */
    private function buildClientData(): Client
    {
        return new Client(
            [
                'name'        => $this->apiService->getParamString('name', true),
                'description' => $this->apiService->getParamString('description'),
                'isGlobal'    => $this->apiService->getParamInt('global'),
            ]
        );
    }
}
