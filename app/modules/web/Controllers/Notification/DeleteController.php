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

namespace SP\Modules\Web\Controllers\Notification;


use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;

/**
 * Class DeleteController
 */
final class DeleteController extends NotificationSaveBase
{
    use ItemTrait;
    use JsonTrait;

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
            if ($id === null) {
                if ($this->userData->getIsAdminApp()) {
                    $this->notificationService->deleteAdminBatch($this->getItemsIdFromRequest($this->request));
                } else {
                    $this->notificationService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));
                }

                $this->eventDispatcher->notify(
                    'delete.notification.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Notifications deleted')))
                );

                return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Notifications deleted'));
            }

            if ($this->userData->getIsAdminApp()) {
                $this->notificationService->deleteAdmin($id);
            } else {
                $this->notificationService->delete($id);
            }

            $this->eventDispatcher->notify(
                'delete.notification',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Notification deleted'))
                        ->addDetail(__u('Notification'), $id)
                )
            );

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Notification deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
