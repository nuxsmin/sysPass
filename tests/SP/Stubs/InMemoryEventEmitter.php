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

namespace SP\Tests\Stubs;

use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Core\Events\EventMessage;

/**
 * Records the events emitted by use cases.
 *
 * @phpstan-type EmittedEvent array{name: string, source: object, message: EventMessage|null}
 */
final class InMemoryEventEmitter implements EventEmitter
{
    /**
     * @var list<EmittedEvent>
     */
    private array $events = [];

    public function emit(string $eventName, object $source, ?EventMessage $message = null): void
    {
        $this->events[] = [
            'name' => $eventName,
            'source' => $source,
            'message' => $message,
        ];
    }

    /**
     * @return list<EmittedEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getEventCount(): int
    {
        return count($this->events);
    }

    /**
     * @return EmittedEvent|null
     */
    public function getLastEvent(): ?array
    {
        if ($this->events === []) {
            return null;
        }

        return $this->events[array_key_last($this->events)];
    }
}
