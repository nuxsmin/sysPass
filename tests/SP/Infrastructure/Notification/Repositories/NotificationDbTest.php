<?php
declare(strict_types=1);
/*
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

namespace SP\Tests\Infrastructure\Notification\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Infrastructure\Notification\Repositories\Notification;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the notification visibility queries.
 */
#[Group('integration')]
final class NotificationDbTest extends DatabaseRepositoryTestCase
{
    private Notification $notification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notification = new Notification(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function searchForUserIdReturnsTheUsersOwnAndTheGlobalNotificationsOnly(): void
    {
        $result = $this->notification->searchForUserId(new ItemSearchDto(), 2);

        self::assertSame([2, 1], $this->ids($result->getDataAsArray(NotificationModel::class)));
    }

    #[Test]
    public function searchForUserIdNeverReturnsNotificationsOfOtherUsers(): void
    {
        $result = $this->notification->searchForUserId(new ItemSearchDto(), 3);

        self::assertSame([2], $this->ids($result->getDataAsArray(NotificationModel::class)));
    }

    #[Test]
    public function searchForAdminReturnsTheUsersOwnTheGlobalAndTheAdminNotifications(): void
    {
        $result = $this->notification->searchForAdmin(new ItemSearchDto(), 2);

        self::assertSame([3, 2, 1], $this->ids($result->getDataAsArray(NotificationModel::class)));
    }

    #[Test]
    public function searchForAdminNeverReturnsNotificationsOfAnotherUser(): void
    {
        $result = $this->notification->searchForAdmin(new ItemSearchDto(), 3);

        self::assertSame([3, 2], $this->ids($result->getDataAsArray(NotificationModel::class)));
    }

    #[Test]
    public function searchForUserIdPaginatesTheResults(): void
    {
        $result = $this->notification->searchForUserId(new ItemSearchDto(null, 1, 1), 2);

        self::assertSame([1], $this->ids($result->getDataAsArray(NotificationModel::class)));
        self::assertSame(2, $result->getTotalNumRows());
    }

    /**
     * @param NotificationModel[] $notifications
     *
     * @return int[]
     */
    private function ids(array $notifications): array
    {
        return array_map(static fn(NotificationModel $notification) => $notification->getId(), $notifications);
    }
}
