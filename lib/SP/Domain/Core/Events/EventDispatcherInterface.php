<?php
declare(strict_types=1);
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

namespace SP\Domain\Core\Events;

use SP\Core\Events\Event;

/**
 * Interface EventDispatcherInterface
 */
interface EventDispatcherInterface
{
    /**
     * Check whether an EventReceiver is attached
     *
     * @param EventReceiver $receiver
     * @return bool
     */
    public function has(EventReceiver $receiver): bool;

    /**
     * Attach an EventReceiver
     *
     * @param EventReceiver $receiver
     * @return void
     */
    public function attach(EventReceiver $receiver): void;

    /**
     * Detach an EventReceiver
     *
     * @param EventReceiver $receiver
     * @return void
     */
    public function detach(EventReceiver $receiver): void;

    /**
     * Notify to receivers
     *
     * @param string $eventName Nombre del evento
     * @param Event $event Objeto del evento
     */
    public function notify(string $eventName, Event $event): void;
}
