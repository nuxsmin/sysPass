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

namespace SP\Providers\Log;

use SP\Core\Events\Event;
use SP\Core\Events\EventReceiver;
use SP\DataModel\EventlogData;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;
use SP\Services\EventLog\EventlogService;
use SplSubject;

/**
 * Class LogHandler
 *
 * @package SP\Providers\Log
 */
class LogHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    const EVENTS = [
        'create.',
        'delete.',
        'edit.',
        'exception',
        'save.',
        'show.account.pass',
        'show.account.link',
        'copy.account.pass',
        'clear.eventlog',
        'login.',
        'logout',
        'track.',
        'acl.deny',
        'check.tempMasterPassword',
        'expire.tempMasterPassword',
        'refresh.masterPassword',
        'update.',
        'import.ldap.',
        'run.',
        'send.mail',
        'show.authToken'
    ];

    /**
     * @var EventlogService
     */
    protected $eventlogService;
    /**
     * @var string
     */
    protected $events;

    /**
     * Receive update from subject
     *
     * @link  http://php.net/manual/en/splobserver.update.php
     * @param SplSubject $subject <p>
     *                            The <b>SplSubject</b> notifying the observer of an update.
     *                            </p>
     * @return void
     * @since 5.1.0
     */
    public function update(SplSubject $subject)
    {
        // TODO: Implement update() method.
    }

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
        $eventlogData = new EventlogData();
        $eventlogData->setAction($eventType);
        $eventlogData->setLevel('INFO');

        if (($e = $event->getSource()) instanceof \Exception) {
            /** @var \Exception $e */
            $eventlogData->setDescription(__($e->getMessage()));
            $eventlogData->setLevel('ERROR');
        } elseif (($eventMessage = $event->getEventMessage()) !== null) {
            $eventlogData->setDescription($eventMessage->composeText());
        }

        try {
            $this->eventlogService->create($eventlogData);
        } catch (\Exception $e) {
            processException($e);
        }
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
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents()
    {
        return self::EVENTS;
    }

    protected function initialize()
    {
        $this->eventlogService = $this->dic->get(EventlogService::class);

        $configEvents = $this->config->getConfigData()->getLogEvents();

        if (count($configEvents) === 0) {
            $this->events = $this->parseEventsToRegex(self::EVENTS);
        } else {
            $this->events = $this->parseEventsToRegex($configEvents);
        }
    }
}