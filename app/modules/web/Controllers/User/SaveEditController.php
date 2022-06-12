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

namespace SP\Modules\Web\Controllers\User;


use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;

/**
 * Class SaveEditController
 */
final class SaveEditController extends UserSaveBase
{
    use JsonTrait, ItemTrait;

    /**
     * Saves edit action
     *
     * @param  int  $id
     *
     * @return bool
     * @throws \JsonException
     */
    public function saveEditAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->form->validateFor(ActionsInterface::USER_EDIT, $id);

            $itemData = $this->form->getItemData();

            $this->userService->update($itemData);

            $this->eventDispatcher->notifyEvent(
                'edit.user',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('User updated'))
                        ->addDetail(__u('User'), $itemData->getName())
                        ->addExtra('userId', $id)
                )
            );

            $this->updateCustomFieldsForItem(ActionsInterface::USER, $id, $this->request, $this->customFieldService);

            $this->checkChangeUserPass($id, $itemData);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('User updated'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}