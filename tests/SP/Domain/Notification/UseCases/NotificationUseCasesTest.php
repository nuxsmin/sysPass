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

namespace SP\Tests\Domain\Notification\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Notification\Models\Notification;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Domain\Notification\UseCases\CheckNotificationUseCase;
use SP\Domain\Notification\UseCases\CreateNotificationUseCase;
use SP\Domain\Notification\UseCases\DeleteNotificationUseCase;
use SP\Domain\Notification\UseCases\DeleteNotificationsUseCase;
use SP\Domain\Notification\UseCases\Dtos\CheckNotificationCommand;
use SP\Domain\Notification\UseCases\Dtos\CreateNotificationCommand;
use SP\Domain\Notification\UseCases\Dtos\DeleteNotificationCommand;
use SP\Domain\Notification\UseCases\Dtos\DeleteNotificationsCommand;
use SP\Domain\Notification\UseCases\Dtos\GetNotificationCommand;
use SP\Domain\Notification\UseCases\Dtos\SearchNotificationsCommand;
use SP\Domain\Notification\UseCases\Dtos\UpdateNotificationCommand;
use SP\Domain\Notification\UseCases\GetNotificationUseCase;
use SP\Domain\Notification\UseCases\SearchNotificationsUseCase;
use SP\Domain\Notification\UseCases\UpdateNotificationUseCase;
use SP\Tests\Generators\NotificationDataGenerator;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the notification use cases.
 */
#[Group('unitary')]
final class NotificationUseCasesTest extends TestCase
{
    private NotificationService&MockObject $service;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(NotificationService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function createStoresTheNotification(): void
    {
        $this->service->method('create')->willReturn(9);

        $result = (new CreateNotificationUseCase($this->service, $this->eventEmitter))
            ->execute(new CreateNotificationCommand(NotificationDataGenerator::factory()->buildNotification()));

        self::assertSame(9, $result->id);
        self::assertEvent('create.notification', 'Notification created');
    }

    #[Test]
    public function updateSavesTheNotification(): void
    {
        $this->service->method('update')->willReturn(9);

        $result = (new UpdateNotificationUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateNotificationCommand(NotificationDataGenerator::factory()->buildNotification()));

        self::assertSame(9, $result->id);
        self::assertEvent('edit.notification', 'Notification updated');
    }

    #[Test]
    public function deleteAsAdminUsesTheAdminDeletion(): void
    {
        $this->service->expects($this->once())->method('deleteAdmin');
        $this->service->expects($this->never())->method('delete');

        $result = (new DeleteNotificationUseCase($this->service, $this->eventEmitter))
            ->execute(new DeleteNotificationCommand(9, true));

        self::assertSame(9, $result->id);
        self::assertEvent('delete.notification', "Notification deleted\nNotification: 9");
    }

    #[Test]
    public function deleteBatchAsUserUsesTheUserDeletion(): void
    {
        $this->service->expects($this->once())->method('deleteByIdBatch');
        $this->service->expects($this->never())->method('deleteAdminBatch');

        $result = (new DeleteNotificationsUseCase($this->service, $this->eventEmitter))
            ->execute(new DeleteNotificationsCommand([1, 2], false));

        self::assertSame([1, 2], $result->ids);
        self::assertEvent('delete.notification.selection', 'Notifications deleted');
    }

    #[Test]
    public function checkMarksTheNotificationAsRead(): void
    {
        $this->service->expects($this->once())->method('setCheckedById')->with(9);

        $result = (new CheckNotificationUseCase($this->service, $this->eventEmitter))
            ->execute(new CheckNotificationCommand(9));

        self::assertSame(9, $result->id);
        self::assertEvent('check.notification', "Notification read\nNotification: 9");
    }

    #[Test]
    public function getReturnsTheNotification(): void
    {
        $notification = NotificationDataGenerator::factory()->buildNotification();
        $this->service->method('getById')->willReturn($notification);

        $result = (new GetNotificationUseCase($this->service))
            ->execute(new GetNotificationCommand(9));

        self::assertSame($notification, $result->notification);
    }

    #[Test]
    public function searchReturnsTheNotifications(): void
    {
        $this->service->method('search')->willReturn(QueryResult::withTotalNumRows([], 6));

        $result = (new SearchNotificationsUseCase($this->service))
            ->execute(new SearchNotificationsCommand(new ItemSearchDto()));

        self::assertSame([], $result->notifications);
        self::assertSame(6, $result->total);
    }

    private function assertEvent(string $name, string $text): void
    {
        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame($name, $event['name']);
        self::assertSame($text, (new EventMessageFormatter())->composeText($event['message']));
    }
}
