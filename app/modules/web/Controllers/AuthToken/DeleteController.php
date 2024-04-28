<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\AuthToken;

use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Http\Dtos\JsonMessage;

/**
 * Class DeleteController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DeleteController extends AuthTokenSaveBase
{
    /**
     * Delete action
     *
     * @param  int|null  $id
     *
     * @return bool
     * @throws JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::AUTHTOKEN_DELETE)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            if ($id === null) {
                $this->authTokenService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(AclActionsInterface::AUTHTOKEN, $id, $this->customFieldService);

                $this->eventDispatcher->notify(
                    'delete.authToken.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Authorizations deleted')))
                );

                return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Authorizations deleted'));
            }

            $this->authTokenService->delete($id);

            $this->deleteCustomFieldsForItem(AclActionsInterface::AUTHTOKEN, $id, $this->customFieldService);

            $this->eventDispatcher->notify(
                'delete.authToken',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Authorization deleted'))
                        ->addDetail(__u('Authorization'), $id)
                )
            );

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Authorization deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
