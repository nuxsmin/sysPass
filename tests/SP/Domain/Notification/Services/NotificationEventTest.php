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

namespace SP\Tests\Domain\Notification\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Notification\Models\Notification;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Domain\Notification\Services\NotificationEvent;
use SP\Tests\UnitaryTestCase;

/**
 * Class NotificationEventTest
 */
#[Group('unitary')]
class NotificationEventTest extends UnitaryTestCase
{

    private MockObject|NotificationService $notificationService;
    private NotificationEvent              $notificationEvent;

    public function testUpdateWithRequestAccount()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('userId', [100, 200]);

        $event = new Event($this, $eventMessage);

        $invokedCount = $this->exactly(2);

        $this->notificationService
            ->expects($invokedCount)
            ->method('create')
            ->with(
                self::callback(static function (Notification $notification) use ($invokedCount) {
                    $userId = match ($invokedCount->numberOfInvocations()) {
                        1 => 100,
                        2 => 200
                    };

                    return $notification->getType() == 'Request'
                           && $notification->getComponent() === 'Accounts'
                           && $notification->getUserId() === $userId
                           && !empty($notification->getDescription());
                })
            );

        $this->notificationEvent->update('request.account', $event);
    }

    public function testUpdateWithRequestAccountAndNoUserId()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value');

        $event = new Event($this, $eventMessage);

        $this->notificationService
            ->expects($this->never())
            ->method('create');

        $this->notificationEvent->update('request.account', $event);
    }

    public function testUpdateWithRequestAccountAndException()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('userId', [100, 200]);

        $event = new Event($this, $eventMessage);

        $invokedCount = $this->exactly(2);

        $this->notificationService
            ->expects($invokedCount)
            ->method('create')
            ->willThrowException(new RuntimeException('test'));

        $this->notificationEvent->update('request.account', $event);
    }

    public function testUpdateWithShowLink()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('notify', [true])
                                    ->setExtra('userId', [100]);

        $event = new Event($this, $eventMessage);

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (Notification $notification) {
                    return $notification->getType() == 'Notification'
                           && $notification->getComponent() === 'Accounts'
                           && $notification->getUserId() === 100
                           && !empty($notification->getDescription());
                })
            );

        $this->notificationEvent->update('show.account.link', $event);
    }

    public function testUpdateWithShowLinkAndException()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('notify', [true])
                                    ->setExtra('userId', [100]);

        $event = new Event($this, $eventMessage);

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new RuntimeException('test'));

        $this->notificationEvent->update('show.account.link', $event);
    }

    public function testUpdateWithShowLinkAndNoNotify()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('userId', [100]);

        $event = new Event($this, $eventMessage);

        $this->notificationService
            ->expects($this->never())
            ->method('create');

        $this->notificationEvent->update('show.account.link', $event);
    }

    public function testUpdateWithShowLinkAndFalseNoNotify()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('notify', [false])
                                    ->setExtra('userId', [100]);

        $event = new Event($this, $eventMessage);

        $this->notificationService
            ->expects($this->never())
            ->method('create');

        $this->notificationEvent->update('show.account.link', $event);
    }

    public function testUpdateWithShowLinkAndNoUserId()
    {
        $eventMessage = EventMessage::build()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('notify', [true]);

        $event = new Event($this, $eventMessage);

        $this->notificationService
            ->expects($this->never())
            ->method('create');

        $this->notificationEvent->update('show.account.link', $event);
    }

    public function testGetEvents()
    {
        $expected = 'request\.account|show\.account\.link';

        $this->assertEquals($expected, $this->notificationEvent->getEvents());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = $this->createMock(NotificationService::class);
        $this->notificationEvent = new NotificationEvent($this->application, $this->notificationService);
    }
}
