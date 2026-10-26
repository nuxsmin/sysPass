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

namespace SP\Core\Events;

use Closure;
use SP\Domain\Core\Exceptions\InvalidClassException;

/**
 * Class Event
 */
readonly class Event
{
    /**
     * @param object $source The emmiter of the event
     * @param EventMessage|Closure|null $eventMessage An {@link EventMessage} or a {@link Closure} that returns an {@link EventMessage}
     */
    public function __construct(
        private object                    $source,
        private EventMessage|Closure|null $eventMessage = null
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $type
     * @return T&object
     * @throws InvalidClassException
     */
    public function getSource(?string $type = null): object
    {
        if ($type !== null && !is_a($this->source, $type)) {
            throw InvalidClassException::error(
                'Source type mismatch',
                sprintf('Source: %s - Expected: %s', get_class($this->source), $type)
            );
        }

        return $this->source;
    }

    public function getEventMessage(): ?EventMessage
    {
        if ($this->eventMessage instanceof Closure) {
            return $this->eventMessage->call($this);
        }

        return $this->eventMessage;
    }
}
