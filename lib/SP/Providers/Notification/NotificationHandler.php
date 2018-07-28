<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Providers\Notification;

use DI\Container;
use SP\Core\Events\Event;
use SP\Core\Events\EventReceiver;
use SP\DataModel\NotificationData;
use SP\Providers\Provider;
use SP\Services\Notification\NotificationService;
use SplSubject;

/**
 * Class NotificationHandler
 *
 * @package SP\Providers\Notification
 */
final class NotificationHandler extends Provider implements EventReceiver
{
    const EVENTS = [
        'request.account',
        'show.account.link'
    ];

    /**
     * @var NotificationService
     */
    protected $notificationService;
    /**
     * @var string
     */
    protected $events;

    /**
     * Inicialización del observador
     */
    public function init()
    {
        // TODO: Implement init() method.
    }

    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event  $event     Objeto del evento
     */
    public function updateEvent($eventType, Event $event)
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
    protected function requestAccountNotification(Event $event)
    {
        $eventMessage = $event->getEventMessage();
        $data = $eventMessage->getData();

        foreach ($data['userId'] as $userId) {
            $notificationData = new NotificationData();
            $notificationData->setType(__('Solicitud'));
            $notificationData->setComponent(__('Cuentas'));
            $notificationData->setUserId($userId);
            $notificationData->setDescription($eventMessage);

            $this->notify($notificationData);
        }
    }

    /**
     * @param NotificationData $notificationData
     */
    protected function notify(NotificationData $notificationData)
    {
        try {
            $this->notificationService->create($notificationData);
        } catch (\Exception $e) {
            processException($e);
        }
    }

    /**
     * @param Event $event
     */
    protected function showAccountLinkNotification(Event $event)
    {
        $eventMessage = $event->getEventMessage();
        $data = $eventMessage->getData();

        if ($data['notify'] === true) {
            $notificationData = new NotificationData();
            $notificationData->setType(__('Notificación'));
            $notificationData->setComponent(__('Cuentas'));
            $notificationData->setUserId($data['userId']);
            $notificationData->setDescription($eventMessage);

            $this->notify($notificationData);
        }
    }

    /**
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents()
    {
        return self::EVENTS;
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEventsString()
    {
        return $this->events;
    }

    /**
     * Receive update from subject
     *
     * @link  http://php.net/manual/en/splobserver.update.php
     *
     * @param SplSubject $subject <p>
     *                            The <b>SplSubject</b> notifying the observer of an update.
     *                            </p>
     *
     * @return void
     * @since 5.1.0
     */
    public function update(SplSubject $subject)
    {
        // TODO: Implement update() method.
    }

    /**
     * @param Container $dic
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function initialize(Container $dic)
    {
        $this->notificationService = $dic->get(NotificationService::class);

        $this->events = str_replace('.', '\\.', implode('|', self::EVENTS));
    }
}