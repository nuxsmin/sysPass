<?php

declare(strict_types=1);
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

namespace SP\Domain\Log\Providers;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Common\Providers\EventsTrait;
use SP\Domain\Common\Providers\Provider;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Security\Models\Eventlog;
use SP\Domain\Security\Ports\EventlogService;
use Throwable;

use function SP\processException;

/**
 * Class DatabaseHandler
 */
final class DatabaseHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    private readonly string $events;

    public function __construct(
        Application                        $application,
        private readonly EventlogService   $eventlogService,
        private readonly LanguageInterface $language
    ) {
        parent::__construct($application);

        $configEvents = $this->config->getConfigData()->getLogEvents();

        if (count($configEvents) === 0) {
            $this->events = $this->parseEventsToRegex(LogInterface::EVENTS_FIXED);
        } else {
            $this->events = $this->parseEventsToRegex(array_merge($configEvents, LogInterface::EVENTS_FIXED));
        }
    }


    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event $event Objeto del evento
     *
     * @throws InvalidClassException
     */
    public function update(string $eventType, Event $event): void
    {
        if (str_contains($eventType, 'database.')) {
            return;
        }

        $this->language->setAppLocales();

        $properties = ['action' => $eventType, 'level' => 'INFO'];

        $source = $event->getSource();

        if ($source instanceof Throwable) {
            $properties['level'] = 'ERROR';
            $properties['description'] = (string)$source;
        } else {
            $properties['description'] = $event->getEventMessage()?->composeText();
        }

        try {
            $this->eventlogService->create(new Eventlog($properties));
        } catch (Exception $e) {
            processException($e);
        }

        $this->language->unsetAppLocales();
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string|null
     */
    public function getEventsString(): ?string
    {
        return $this->events;
    }
}
