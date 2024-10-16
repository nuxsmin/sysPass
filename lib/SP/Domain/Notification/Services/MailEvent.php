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
use SP\Core\Messages\MailMessage;
use SP\Core\Messages\TextFormatter;
use SP\Domain\Common\Attributes\EventReceiver as EventReceiverAttribute;
use SP\Domain\Common\Services\EventReceiver as EventReceiverTrait;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Notification\Ports\MailService;

use function SP\__;
use function SP\processException;

/**
 * Class MailEvent
 */
#[EventReceiverAttribute('clear.eventlog')]
#[EventReceiverAttribute('refresh.masterPassword')]
#[EventReceiverAttribute('update.masterPassword.start')]
#[EventReceiverAttribute('update.masterPassword.end')]
#[EventReceiverAttribute('request.account')]
#[EventReceiverAttribute('edit.user.password')]
#[EventReceiverAttribute('save.config.')]
#[EventReceiverAttribute('create.tempMasterPassword')]
final class MailEvent extends Service implements EventReceiver
{
    use EventReceiverTrait;

    public const EVENTS = [
        'create.',
        'delete.',
        'edit.',
        'save.',
        'import.ldap.end',
        'run.backup.end',
        'run.import.end',
    ];

    public function __construct(
        Application                  $application,
        private readonly MailService $mailService,
        private readonly RequestService $request
    ) {
        parent::__construct($application);

        $this->setupEvents($this->config->getConfigData()->getMailEvents() ?? []);
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEvents(): string
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
        if (($eventMessage = $event->getEventMessage()) !== null) {
            try {
                $recipients = $eventMessage->getExtra('email')
                              ?? $this->config->getConfigData()->getMailRecipients()
                                 ?? [];

                $to = array_filter($recipients);

                if (empty($to)) {
                    return;
                }

                $userData = $this->context->getUserData();

                $mailMessage = new MailMessage();

                if ($eventMessage->getDescriptionCounter() === 0
                    && $eventMessage->getDetailsCounter() === 0
                ) {
                    $mailMessage->addDescription(sprintf(__('Event: %s'), $eventType));
                } else {
                    $mailMessage->addDescription($eventMessage->composeText('<br>'));
                }

                $mailMessage->addDescriptionLine();

                $mailMessage->addDescription(
                    sprintf(
                        __('Performed by: %s (%s)'),
                        $userData->name ?? 'sysPass',
                        $userData->login ?? 'APP'
                    )
                );

                $mailMessage->addDescription(
                    sprintf(
                        __('IP Address: %s'),
                        $this->request->getClientAddress(true)
                    )
                );

                $subject = $eventMessage->getDescription(new TextFormatter(), true) ?: $eventType;

                $this->mailService->send($subject, $to, $mailMessage);
            } catch (Exception $e) {
                processException($e);
            }
        }
    }
}
