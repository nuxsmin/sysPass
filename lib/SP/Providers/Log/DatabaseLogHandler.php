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

use DI\Container;
use SP\Core\Events\Event;
use SP\Core\Events\EventReceiver;
use SP\Core\Language;
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
final class DatabaseLogHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    /**
     * @var EventlogService
     */
    private $eventlogService;
    /**
     * @var string
     */
    private $events;
    /**
     * @var Language
     */
    private $language;

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
        $this->language->setAppLocales();

        $eventlogData = new EventlogData();
        $eventlogData->setAction($eventType);
        $eventlogData->setLevel('INFO');

        if (($e = $event->getSource()) instanceof \Exception) {
            /** @var \Exception $e */
            $eventlogData->setLevel('ERROR');
            $eventlogData->setDescription(__($e->getMessage()));
        } elseif (($eventMessage = $event->getEventMessage()) !== null) {
            $eventlogData->setDescription($eventMessage->composeText());
        }

        try {
            $this->eventlogService->create($eventlogData);
        } catch (\Exception $e) {
            processException($e);
        }

        $this->language->unsetAppLocales();
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
        return LogInterface::EVENTS;
    }

    /**
     * @param Container $dic
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function initialize(Container $dic)
    {
        $this->language = $dic->get(Language::class);
        $this->eventlogService = $dic->get(EventlogService::class);

        $configEvents = $this->config->getConfigData()->getLogEvents();

        if (count($configEvents) === 0) {
            $this->events = $this->parseEventsToRegex(LogInterface::EVENTS);
        } else {
            $this->events = $this->parseEventsToRegex($configEvents);
        }
    }
}