<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\SPException;
use function SP\__u;

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
     * @param int|null $id
     *
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function deleteAction(?int $id = null): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::AUTHTOKEN_DELETE)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        if ($id === null) {
            $ids = array_map('intval', $this->getItemsIdFromRequest($this->request) ?? []);
            $result = $this->authTokenService->deleteByIdBatch($ids);

            if ($result['deleted'] !== []) {
                $this->eventDispatcher->notify(
                    'delete.authToken.selection',
                    new Event($this, EventMessage::build(__u('Authorizations deleted')))
                );
            }

            if ($result['failed'] === []) {
                return ActionResponse::ok(__u('Authorizations deleted'));
            }

            if ($result['deleted'] !== []) {
                return ActionResponse::warning(__u('Some authorizations could not be deleted'), $result);
            }

            $uniqueReasons = array_values(array_unique($result['failed']));
            if ($uniqueReasons === [__u('You don\'t have permission to do this operation')]) {
                throw new UnauthorizedActionException();
            }

            return ActionResponse::error(__u('Error while removing the tokens'), $result);
        }

        $this->authTokenService->delete($id);

        $this->eventDispatcher->notify(
            'delete.authToken',
            new Event(
                $this,
                EventMessage::build(__u('Authorization deleted'))->addDetail(__u('Authorization'), $id)
            )
        );

        return ActionResponse::ok(__u('Authorization deleted'));
    }
}
