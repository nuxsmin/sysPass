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

namespace SPT\Core\Events;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Core\Events\EventReceiver;
use SPT\UnitaryTestCase;

/**
 * Class EventDispatcherTest
 *
 */
#[Group('unitary')]
class EventDispatcherTest extends UnitaryTestCase
{
    private const VALID_EVENTS = 'test|foo.bar.event';
    private EventReceiver|MockObject $eventReceiver;
    private EventDispatcher          $eventDispatcher;

    public static function eventNameProvider(): array
    {
        return [
            ['test'],
            ['foo.bar.event']
        ];
    }

    public function testAttach()
    {
        $this->eventDispatcher->attach($this->eventReceiver);

        self::assertTrue($this->eventDispatcher->has($this->eventReceiver));
    }

    public function testDetach()
    {
        $this->eventDispatcher->attach($this->eventReceiver);
        $this->eventDispatcher->detach($this->eventReceiver);

        self::assertFalse($this->eventDispatcher->has($this->eventReceiver));
    }

    /**
     * @param string $eventName
     */
    #[DataProvider('eventNameProvider')]
    public function testNotify(string $eventName)
    {
        $event = new Event($this);

        $this->eventReceiver->expects(self::once())
                            ->method('getEventsString')
                            ->willReturn(self::VALID_EVENTS);

        $this->eventReceiver->expects(self::once())
                            ->method('update')
                            ->with($eventName, $event);

        $this->eventDispatcher->attach($this->eventReceiver);
        $this->eventDispatcher->notify($eventName, $event);
    }

    public function testNotifyWithWildcard()
    {
        $event = new Event($this);

        $this->eventReceiver->expects(self::once())
                            ->method('getEventsString')
                            ->willReturn('*');

        $this->eventReceiver->expects(self::once())
                            ->method('update')
                            ->with('test', $event);

        $this->eventDispatcher->attach($this->eventReceiver);
        $this->eventDispatcher->notify('test', $event);
    }

    public function testNotifyWithInvalidEvent()
    {
        $this->eventReceiver->expects(self::once())
                            ->method('getEventsString')
                            ->willReturn('anotherEvent');

        $this->eventReceiver->expects(self::never())
                            ->method('update');

        $this->eventDispatcher->attach($this->eventReceiver);
        $this->eventDispatcher->notify('test', new Event($this));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventReceiver = $this->createMock(EventReceiver::class);

        $this->eventDispatcher = new EventDispatcher();
    }

}
