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

namespace SP\Providers\Mail;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Messages\MailMessage;
use SP\Core\Messages\TextFormatter;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Notification\Ports\MailService;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;

use function SP\__;
use function SP\processException;

/**
 * Class MailHandler
 *
 * @package SP\Providers\Mail
 */
final class MailHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    public const EVENTS = [
        'create.',
        'delete.',
        'edit.',
        'save.',
        'import.ldap.end',
        'run.backup.end',
        'run.import.end',
    ];

    public const EVENTS_FIXED = [
        'clear.eventlog',
        'refresh.masterPassword',
        'update.masterPassword.start',
        'update.masterPassword.end',
        'request.account',
        'edit.user.password',
        'save.config.',
        'create.tempMasterPassword',
    ];

    private string $events;

    public function __construct(
        Application                       $application,
        private readonly MailService      $mailService,
        private readonly RequestInterface $request
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
        if (($eventMessage = $event->getEventMessage()) !== null) {
            try {
                $configData = $this->config->getConfigData();
                $emails = $eventMessage->getExtra('email');

                if ($emails && count($emails) > 0) {
                    $recipients = $emails;
                } else {
                    $recipients = $configData->getMailRecipients();
                }

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

                if ($userData->getId() !== null) {
                    $mailMessage->addDescription(
                        sprintf(
                            __('Performed by: %s (%s)'),
                            $userData->getName(),
                            $userData->getLogin()
                        )
                    );
                } else {
                    $mailMessage->addDescription(
                        sprintf(
                            __('Performed by: %s (%s)'),
                            'sysPass',
                            'APP'
                        )
                    );
                }

                $mailMessage->addDescription(
                    sprintf(
                        __('IP Address: %s'),
                        $this->request->getClientAddress(true)
                    )
                );

                $subject = $eventMessage->getDescription(new TextFormatter(), true) ?: $eventType;

                $this->mailService->send(
                    $subject,
                    $to,
                    $mailMessage
                );
            } catch (Exception $e) {
                processException($e);
            }
        }
    }

    public function initialize(): void
    {
        $configEvents = $this->config->getConfigData()->getMailEvents();

        if (count($configEvents) === 0) {
            $this->events = $this->parseEventsToRegex(self::EVENTS_FIXED);
        } else {
            $this->events = $this->parseEventsToRegex(array_merge($configEvents, self::EVENTS_FIXED));
        }

        $this->initialized = true;
    }
}
