<?php
/**
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

declare(strict_types=1);

namespace SP\Domain\Notification\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Common\Attributes\EventReceiver as EventReceiverAttribute;
use SP\Domain\Common\Services\EventReceiver as EventReceiverTrait;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Notification\Models\Notification;
use SP\Domain\Notification\Ports\NotificationService;

use function SP\__;
use function SP\processException;

/**
 * Class NotificationEvent
 */
#[EventReceiverAttribute('request.account')]
#[EventReceiverAttribute('show.account.link')]
final class NotificationEvent extends Service implements EventReceiver
{
    use EventReceiverTrait;

    public function __construct(
        Application $application,
        private readonly NotificationService $notificationService
    ) {
        parent::__construct($application);

        $this->setupEvents();
    }

    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event $event Objeto del evento
     */
    public function update(string $eventType, Event $event): void
    {
        switch ($eventType) {
            case 'request.account':
                $this->requestAccountNotification($event);
                break;
            case 'show.account.link':
                $this->showAccountLinkNotification($event);
                break;
        }
    }

    /**
     * @param Event $event
     */
    private function requestAccountNotification(Event $event): void
    {
        $eventMessage = $event->getEventMessage();
        $userIds = $eventMessage?->getExtra('userId') ?? [];

        foreach ($userIds as $userId) {
            $notification = new Notification(
                [
                    'type' => __('Request'),
                    'component' => __('Accounts'),
                    'userId' => $userId,
                    'description' => $eventMessage->composeHtml()
                ]
            );

            $this->notify($notification);
        }
    }

    /**
     * @param Notification $notificationData
     */
    private function notify(Notification $notificationData): void
    {
        try {
            $this->notificationService->create($notificationData);
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * @param Event $event
     */
    private function showAccountLinkNotification(Event $event): void
    {
        $eventMessage = $event->getEventMessage();
        $notify = $eventMessage?->getExtra('notify')[0] ?? null;
        $userId = $eventMessage?->getExtra('userId')[0] ?? null;

        if ($notify === true && $userId) {
            $notification = new Notification(
                [
                    'type' => __('Notification'),
                    'component' => __('Accounts'),
                    'userId' => $userId,
                    'description' => $eventMessage->composeHtml()
                ]
            );

            $this->notify($notification);
        }
    }
}
