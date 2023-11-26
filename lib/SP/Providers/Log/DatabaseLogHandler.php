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

namespace SP\Providers\Log;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\DataModel\EventlogData;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Security\Ports\EventlogServiceInterface;
use SP\Domain\Security\Services\EventlogService;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;
use SplSubject;

/**
 * Class LogHandler
 *
 * @package SP\Providers\Log
 */
final class DatabaseLogHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    private EventlogService $eventlogService;
    private Language        $language;
    private string          $events;

    public function __construct(
        Application $application,
        EventlogServiceInterface $eventlogService,
        LanguageInterface $language
    ) {
        $this->eventlogService = $eventlogService;
        $this->language = $language;

        parent::__construct($application);
    }


    /**
     * Receive update from subject
     *
     * @link  http://php.net/manual/en/splobserver.update.php
     *
     * @param  SplSubject  $subject  <p>
     *                            The <b>SplSubject</b> notifying the observer of an update.
     *                            </p>
     *
     * @return void
     * @throws InvalidClassException
     * @since 5.1.0
     */
    public function update(SplSubject $subject): void
    {
        $this->update('update', new Event($subject));
    }

    /**
     * Evento de actualización
     *
     * @param  string  $eventType  Nombre del evento
     * @param  Event  $event  Objeto del evento
     *
     * @throws InvalidClassException
     */
    public function update(string $eventType, Event $event): void
    {
        if (strpos($eventType, 'database.') !== false) {
            return;
        }

        $this->language->setAppLocales();

        $eventlogData = new EventlogData();
        $eventlogData->setAction($eventType);
        $eventlogData->setLevel('INFO');

        $source = $event->getSource();

        if ($source instanceof SPException) {
            $eventlogData->setLevel('ERROR');

            $hint = $source->getHint();

            if ($hint !== null) {
                $eventlogData->setDescription(__($source->getMessage()).PHP_EOL.$hint);
            } else {
                $eventlogData->setDescription(__($source->getMessage()));
            }
        } elseif ($source instanceof Exception) {
            $eventlogData->setLevel('ERROR');
            $eventlogData->setDescription(__($source->getMessage()));
        } elseif (($eventMessage = $event->getEventMessage()) !== null) {
            $eventlogData->setDescription($eventMessage->composeText());
        }

        try {
            $this->eventlogService->create($eventlogData);
        } catch (Exception $e) {
            processException($e);
        }

        $this->language->unsetAppLocales();
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEventsString(): ?string
    {
        return $this->events;
    }

    /**
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents(): array
    {
        return LogInterface::EVENTS;
    }

    public function initialize(): void
    {
        $configEvents = $this->config->getConfigData()->getLogEvents();

        if (count($configEvents) === 0) {
            $this->events = $this->parseEventsToRegex(LogInterface::EVENTS_FIXED);
        } else {
            $this->events = $this->parseEventsToRegex(array_merge($configEvents, LogInterface::EVENTS_FIXED));
        }

        $this->initialized = true;
    }
}
