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

namespace SP\Core\Events;

use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventReceiver;
use SplObjectStorage;

use function SP\logger;

/**
 * Class EventDispatcherBase
 *
 * @package SP\Core\Events
 */
abstract class EventDispatcherBase implements EventDispatcherInterface
{
    protected SplObjectStorage $receivers;

    final public function __construct()
    {
        $this->receivers = new SplObjectStorage();
    }

    /**
     * Check whether an EventReceiver is attached
     *
     * @param EventReceiver $receiver
     * @return bool
     */
    final public function has(EventReceiver $receiver): bool
    {
        return $this->receivers->contains($receiver);
    }

    /**
     * Attach an EventReceiver
     *
     * @param EventReceiver $receiver
     * @return void
     */
    final public function attach(EventReceiver $receiver): void
    {
        logger('Attach: ' . $receiver::class);

        $this->receivers->attach($receiver);
    }

    /**
     * Detach an EventReceiver
     *
     * @param EventReceiver $receiver
     * @return void
     */
    final public function detach(EventReceiver $receiver): void
    {
        logger('Detach: ' . $receiver::class);

        $this->receivers->detach($receiver);
    }

    /**
     * Notify to receivers
     *
     * @param string $eventName event's name
     * @param Event $event event's object
     *
     * TODO: Include event's name in Event object and simplify the method's signature
     */
    final public function notify(string $eventName, Event $event): void
    {
        /** @var EventReceiver $receiver */
        foreach ($this->receivers as $receiver) {
            $events = $receiver->getEventsString();

            if ($events === '*' || preg_match(sprintf('/%s/i', $events), $eventName)) {
                $receiver->update($eventName, $event);
            }
        }
    }
}
