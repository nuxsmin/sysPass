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

namespace SP\Providers\Notification;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\DataModel\NotificationItemWithIdAndName;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Notification\Ports\NotificationServiceInterface;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;

use function SP\__;
use function SP\processException;

/**
 * Class NotificationHandler
 *
 * @package SP\Providers\Notification
 */
final class NotificationHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    public const EVENTS = [
        'request.account',
        'show.account.link',
    ];

    private string $events;

    public function __construct(
        Application                                   $application,
        private readonly NotificationServiceInterface $notificationService
    ) {
        parent::__construct($application);
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEventsString(): string
    {
        return $this->events;
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
        $userIds = $eventMessage !== null ? $eventMessage->getExtra('userId') : [];

        foreach ($userIds as $userId) {
            $notificationData = new NotificationItemWithIdAndName();
            $notificationData->setType(__('Request'));
            $notificationData->setComponent(__('Accounts'));
            $notificationData->setUserId($userId);
            $notificationData->setDescription($eventMessage, true);

            $this->notify($notificationData);
        }
    }

    /**
     * @param NotificationItemWithIdAndName $notificationData
     */
    private function notify(NotificationItemWithIdAndName $notificationData): void
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
        $notify = $eventMessage !== null ? $eventMessage->getExtra('notify') : [];

        if ($notify[0] === true) {
            $userId = $eventMessage->getExtra('userId')[0];

            $notificationData = new NotificationItemWithIdAndName();
            $notificationData->setType(__('Notification'));
            $notificationData->setComponent(__('Accounts'));
            $notificationData->setUserId($userId);
            $notificationData->setDescription($eventMessage, true);

            $this->notify($notificationData);
        }
    }

    public function initialize(): void
    {
        $this->events = $this->parseEventsToRegex(self::EVENTS);
        $this->initialized = true;
    }
}
