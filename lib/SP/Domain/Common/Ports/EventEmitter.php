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

namespace SP\Domain\Common\Ports;

use SP\Domain\Core\Events\EventMessage;

/**
 * Publishes audit and notification events from use cases.
 *
 * This is the only event dependency a use case takes. It exposes publishing
 * only; attaching or detaching receivers is an interface-layer concern.
 *
 * Conventions:
 * - the event name is the existing one (for example `create.tag`), because
 *   receivers and the mail event configuration match on it;
 * - the source is the use case that produced the event;
 * - message descriptions and detail keys are untranslated message keys. The
 *   receiver translates them when it composes the log line, mail or
 *   notification (P4-2 translation policy);
 * - a null message reproduces events that only carry their name (`new Event($this)`);
 * - extras required by receivers (`userId`, `userProfileId`, `userGroupId`,
 *   `notify`) are part of the message;
 * - emit after the state change has succeeded, never before.
 */
interface EventEmitter
{
    /**
     * @param string $eventName Existing event name, for example `create.tag`
     * @param object $source The object that produced the event, usually the use case
     * @param EventMessage<mixed>|null $message Untranslated message keys, or null for a message-less event
     */
    public function emit(string $eventName, object $source, ?EventMessage $message = null): void;
}
