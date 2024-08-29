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

namespace SP\Modules\Web\Controllers\Client;


use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;

use function SP\__u;

/**
 * Class SaveEditController
 */
final class SaveEditController extends ClientSaveBase
{
    use ItemTrait;
    use JsonTrait;

    /**
     * Saves edit action
     *
     * @param int $id
     *
     * @return ActionResponse
     * @throws ValidationException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function saveEditAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::CLIENT_EDIT)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        $this->form->validateFor(AclActionsInterface::CLIENT_EDIT, $id);

        $this->clientService->update($this->form->getItemData());

        $this->eventDispatcher->notify(
            'edit.client',
            new Event(
                $this,
                EventMessage::build()->addDescription(__u('Client updated'))->addDetail(__u('Client'), $id)
            )
        );

        $this->updateCustomFieldsForItem(
            AclActionsInterface::CLIENT,
            $id,
            $this->request,
            $this->customFieldService
        );

        return ActionResponse::ok(__u('Client updated'));
    }
}
