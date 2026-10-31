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

namespace SP\Tests\Core\Events;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Events\EventDispatcherEmitter;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Tests\UnitaryTestCase;

/**
 * Guards the adapter between the use case event port and the event dispatcher.
 */
#[Group('unitary')]
final class EventDispatcherEmitterTest extends UnitaryTestCase
{
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private EventDispatcherEmitter $emitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->emitter = new EventDispatcherEmitter($this->eventDispatcher);
    }

    #[Test]
    public function testEmitNotifiesWithTheEventSourceAndMessage(): void
    {
        $source = new \stdClass();
        $message = EventMessage::build('Tag added');

        $this->eventDispatcher->expects(self::once())
             ->method('notify')
             ->with(
                 'create.tag',
                 self::callback(
                     static function (Event $event) use ($source, $message): bool {
                         return $event->getSource() === $source
                                && $event->getEventMessage() === $message;
                     }
                 )
             );

        $this->emitter->emit('create.tag', $source, $message);
    }

    #[Test]
    public function testEmitSupportsEventsWithoutAMessage(): void
    {
        $source = new \stdClass();

        $this->eventDispatcher->expects(self::once())
             ->method('notify')
             ->with(
                 'delete.tag',
                 self::callback(
                     static fn(Event $event): bool => $event->getSource() === $source
                                                      && $event->getEventMessage() === null
                 )
             );

        $this->emitter->emit('delete.tag', $source);
    }
}
