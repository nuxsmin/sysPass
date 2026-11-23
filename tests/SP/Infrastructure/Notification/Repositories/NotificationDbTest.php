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
 * DB-backed tests for Notification repository CRUD, visibility, batching, and active/date queries.
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

    #[Test]
    public function createGetUpdateAndSetCheckedRoundTrip(): void
    {
        $id = $this->notification->create(new NotificationModel([
            'type' => 'Notice',
            'component' => 'Accounts',
            'description' => 'Created notification',
            'userId' => 2,
            'sticky' => false,
            'onlyAdmin' => false,
            'checked' => false,
        ]))->getLastId();

        self::assertGreaterThan(3, $id);

        $created = $this->notification->getById($id)->getData(NotificationModel::class);
        self::assertInstanceOf(NotificationModel::class, $created);
        self::assertSame('Notice', $created->getType());
        self::assertSame('Accounts', $created->getComponent());
        self::assertSame('Created notification', $created->getDescription());
        self::assertSame(2, $created->getUserId());
        self::assertFalse((bool)$created->isChecked());
        self::assertGreaterThan(1700000000, (int)$created->getDate());

        // update() with a model that already has a non-null date (e.g. loaded from DB or DTO) must succeed
        $affected = $this->notification->update(new NotificationModel([
            'id' => $id,
            'type' => 'Warning',
            'component' => 'Security',
            'description' => 'Updated notification',
            'date' => 1500000000,
            'userId' => 3,
            'sticky' => true,
            'onlyAdmin' => false,
            'checked' => false,
        ]));
        self::assertSame(1, $affected);

        $updated = $this->notification->getById($id)->getData(NotificationModel::class);
        self::assertSame('Warning', $updated->getType());
        self::assertSame('Security', $updated->getComponent());
        self::assertSame('Updated notification', $updated->getDescription());
        self::assertSame(3, $updated->getUserId());
        self::assertTrue((bool)$updated->isSticky());
        self::assertGreaterThan(1700000000, (int)$updated->getDate());

        self::assertSame(1, $this->notification->setCheckedById($id));
        $checked = $this->notification->getById($id)->getData(NotificationModel::class);
        self::assertTrue((bool)$checked->isChecked());
    }

    #[Test]
    public function getAllGetByIdBatchAndDeleteVariantsHandleStickyAndEmptyInputs(): void
    {
        $all = $this->notification->getAll()->getDataAsArray(NotificationModel::class);
        self::assertSame([1, 2, 3], $this->ids($all));

        // Empty batch returns empty QueryResult cleanly without SQL syntax error
        self::assertSame(0, $this->notification->getByIdBatch([])->getNumRows());

        $batch = $this->notification->getByIdBatch([1, 2])->getDataAsArray(NotificationModel::class);
        self::assertSame([1, 2], $this->ids($batch));

        // Non-admin delete preserves sticky notification (id=2), deletes non-sticky (id=1)
        self::assertSame(0, $this->notification->delete(2)->getAffectedNumRows());
        self::assertSame(1, $this->notification->delete(1)->getAffectedNumRows());
        self::assertSame([2, 3], $this->ids($this->notification->getAll()->getDataAsArray(NotificationModel::class)));

        // Non-admin batch delete skips sticky (id=2) and deletes non-sticky (id=3)
        self::assertSame(1, $this->notification->deleteByIdBatch([2, 3])->getAffectedNumRows());
        self::assertSame([2], $this->ids($this->notification->getAll()->getDataAsArray(NotificationModel::class)));

        // Admin delete removes sticky notification (id=2)
        self::assertSame(1, $this->notification->deleteAdmin(2)->getAffectedNumRows());
        self::assertSame(0, $this->notification->getAll()->getNumRows());

        $idA = $this->notification->create(new NotificationModel([
            'type' => 'T',
            'component' => 'Accounts',
            'description' => 'Sticky A',
            'sticky' => true,
        ]))->getLastId();
        $idB = $this->notification->create(new NotificationModel([
            'type' => 'T',
            'component' => 'Accounts',
            'description' => 'Normal B',
            'sticky' => false,
        ]))->getLastId();

        self::assertSame(2, $this->notification->deleteAdminBatch([$idA, $idB])->getAffectedNumRows());
        self::assertSame(0, $this->notification->getAll()->getNumRows());
    }

    #[Test]
    public function userAndAdminScopeAndDateQueriesFilterCorrectly(): void
    {
        $recentUser2Id = $this->notification->create(new NotificationModel([
            'type' => 'Request',
            'component' => 'Accounts',
            'description' => 'Recent user 2 active',
            'userId' => 2,
            'sticky' => false,
            'onlyAdmin' => false,
            'checked' => false,
        ]))->getLastId();

        $otherComponentId = $this->notification->create(new NotificationModel([
            'type' => 'Notice',
            'component' => 'Config',
            'description' => 'Recent user 2 config',
            'userId' => 2,
            'sticky' => false,
            'onlyAdmin' => false,
            'checked' => true,
        ]))->getLastId();

        $byDate = $this->notification->getForUserIdByDate('Accounts', 2)->getDataAsArray(NotificationModel::class);
        self::assertSame([$recentUser2Id], $this->ids($byDate));

        $allForUser2 = $this->notification->getAllForUserId(2)->getDataAsArray(NotificationModel::class);
        self::assertContains($recentUser2Id, $this->ids($allForUser2));
        self::assertContains($otherComponentId, $this->ids($allForUser2));
        self::assertContains(2, $this->ids($allForUser2));
        self::assertContains(1, $this->ids($allForUser2));
        self::assertNotContains(3, $this->ids($allForUser2));

        $activeForUser2 = $this->notification->getAllActiveForUserId(2)->getDataAsArray(NotificationModel::class);
        self::assertContains($recentUser2Id, $this->ids($activeForUser2));
        self::assertContains(2, $this->ids($activeForUser2));
        self::assertContains(1, $this->ids($activeForUser2));
        self::assertNotContains($otherComponentId, $this->ids($activeForUser2));
        self::assertNotContains(3, $this->ids($activeForUser2));

        $activeForAdmin2 = $this->notification->getAllActiveForAdmin(2)->getDataAsArray(NotificationModel::class);
        self::assertContains($recentUser2Id, $this->ids($activeForAdmin2));
        self::assertContains(3, $this->ids($activeForAdmin2));
        self::assertContains(2, $this->ids($activeForAdmin2));
        self::assertContains(1, $this->ids($activeForAdmin2));
        self::assertNotContains($otherComponentId, $this->ids($activeForAdmin2));
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
