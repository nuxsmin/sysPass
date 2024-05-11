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

namespace SP\Domain\Common\Services;

use ReflectionAttribute;
use ReflectionClass;
use SP\Domain\Common\Attributes\EventReceiver as EventReceiverAttribute;

/**
 * Trait Receiver
 */
trait EventReceiver
{
    private readonly string $events;

    private function setupEvents(array $userEvents = []): void
    {
        $reflectionClass = new ReflectionClass($this);

        $events = array_map(
            static function (ReflectionAttribute $attribute) {
                /** @var EventReceiverAttribute $instance */
                $instance = $attribute->newInstance();

                return $instance->eventName;
            },
            $reflectionClass->getAttributes(EventReceiverAttribute::class)
        );

        $this->events = $this->parseEventsToRegex(array_merge($userEvents, $events));
    }

    /**
     * @param array $events
     *
     * @return string
     */
    private function parseEventsToRegex(array $events): string
    {
        return implode('|', array_map('preg_quote', $events));
    }
}
