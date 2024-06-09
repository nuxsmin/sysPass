<?php
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

declare(strict_types=1);

namespace SP\Tests\Core\Events;

use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\InvalidClassException;
use stdClass;

/**
 * Class EventTest
 */
#[Group('unitary')]
class EventTest extends TestCase
{

    /**
     * @throws InvalidClassException
     */
    public function testGetSource()
    {
        $object = (object)[1];
        $event = new Event($object, EventMessage::factory());

        $out = $event->getSource(stdClass::class);

        $this->assertEquals($object, $out);
    }

    /**
     * @throws InvalidClassException
     */
    public function testGetSourceWithException()
    {
        $object = (object)[1];
        $event = new Event($object, EventMessage::factory());

        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessage('Source type mismatch');

        $event->getSource(Exception::class);
    }

    public function testGetEventMessage()
    {
        $eventMessage = EventMessage::factory()->addDescription('test');
        $event = new Event($this, $eventMessage);

        $this->assertEquals($eventMessage, $event->getEventMessage());
    }
}
