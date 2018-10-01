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

namespace SP\Providers\Mail;

use DI\Container;
use SP\Core\Events\Event;
use SP\Core\Events\EventReceiver;
use SP\Core\Messages\MailMessage;
use SP\Http\Request;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;
use SP\Services\Mail\MailService;
use SplSubject;

/**
 * Class MailHandler
 *
 * @package SP\Providers\Mail
 */
final class MailHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    const EVENTS = [
        'create.',
        'delete.',
        'edit.',
        'save.',
        'clear.eventlog',
        'refresh.masterPassword',
        'update.masterPassword.end',
        'import.ldap.end',
        'run.backup.end',
        'run.import.end'
    ];

    /**
     * @var \SP\Services\Mail\MailService
     */
    private $mailService;
    /**
     * @var string
     */
    private $events;
    /**
     * @var Request
     */
    private $request;

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
        $this->updateEvent('update', new Event($subject));
    }

    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event  $event     Objeto del evento
     */
    public function updateEvent($eventType, Event $event)
    {
        if (($eventMessage = $event->getEventMessage()) !== null) {
            try {
                $configData = $this->config->getConfigData();
                $userData = $this->context->getUserData();

                $mailMessage = new MailMessage();
                $mailMessage->addDescription($eventMessage->composeText());
                $mailMessage->addDescription(sprintf(__('Realizado por: %s (%s)'), $userData->getName(), $userData->getLogin()));
                $mailMessage->addDescription(sprintf(__('Dirección IP: %s'), $this->request->getClientAddress(true)));

                $this->mailService->send($eventMessage->getDescription(true), $configData->getMailFrom(), $mailMessage);
            } catch (\Exception $e) {
                processException($e);
            }
        }
    }

    /**
     * @param Container $dic
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function initialize(Container $dic)
    {
        $this->mailService = $dic->get(MailService::class);
        $this->request = $dic->get(Request::class);

        $configEvents = $this->config->getConfigData()->getMailEvents();

        if (count($configEvents) === 0) {
            $this->events = $this->parseEventsToRegex(self::EVENTS);
        } else {
            $this->events = $this->parseEventsToRegex($configEvents);
        }
    }
}