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
use SP\Domain\Common\Attributes\EventReceiver as EventReceiverAttribute;
use SP\Domain\Common\Ports\TranslationService;
use SP\Domain\Common\Services\EventReceiver as EventReceiverTrait;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Core\Messages\EventMessageFormatterInterface;
use SP\Domain\Core\Log\LoggerInterface;
use SP\Domain\Core\Log\NullLogger;
use SP\Domain\Notification\Models\Notification;
use SP\Domain\Notification\Ports\NotificationService;

use function SP\__u;

/**
 * Class NotificationEvent
 */
#[EventReceiverAttribute('request.account')]
#[EventReceiverAttribute('show.account.link')]
final class NotificationEvent extends Service implements EventReceiver
{
    use EventReceiverTrait;

    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly NotificationService           $notificationService,
        private readonly EventMessageFormatterInterface $messageFormatter,
        private readonly TranslationService             $translationService,
        private readonly LoggerInterface               $logger = new NullLogger()
    ) {
        parent::__construct($config, $eventDispatcher, $context);

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

        if ($eventMessage === null) {
            return;
        }

        $userIds = $eventMessage->getExtra('userId') ?? [];

        foreach ($userIds as $userId) {
            $notification = new Notification(
                [
                    'type' => $this->translationService->translate(__u('Request')),
                    'component' => $this->translationService->translate(__u('Accounts')),
                    'userId' => $userId,
                    'description' => $this->messageFormatter->composeHtml($eventMessage)
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
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param Event $event
     */
    private function showAccountLinkNotification(Event $event): void
    {
        $eventMessage = $event->getEventMessage();

        if ($eventMessage === null) {
            return;
        }

        $notify = $eventMessage->getExtra('notify')[0] ?? null;
        $userId = $eventMessage->getExtra('userId')[0] ?? null;

        if ($notify === true && $userId) {
            $notification = new Notification(
                [
                    'type' => $this->translationService->translate(__u('Notification')),
                    'component' => $this->translationService->translate(__u('Accounts')),
                    'userId' => $userId,
                    'description' => $this->messageFormatter->composeHtml($eventMessage)
                ]
            );

            $this->notify($notification);
        }
    }
}
