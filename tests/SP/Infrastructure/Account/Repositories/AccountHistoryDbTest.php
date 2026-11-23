<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Infrastructure\Account\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Infrastructure\Account\Repositories\AccountHistory;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the AccountHistory repository queries.
 */
#[Group('integration')]
final class AccountHistoryDbTest extends DatabaseRepositoryTestCase
{
    private AccountHistory $accountHistory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountHistory = new AccountHistory(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getAllReturnsTheHistoryRowsNewestFirst(): void
    {
        $rows = $this->accountHistory->getAll()->getDataAsArray();

        self::assertSame([7, 6, 5, 4, 3], array_map(static fn($row) => (int)$row->id, $rows));
    }

    #[Test]
    public function getByIdReturnsTheHistoryEntry(): void
    {
        $result = $this->accountHistory->getById(5);

        self::assertSame(1, $result->getNumRows());

        $history = $result->getData(AccountHistoryModel::class);

        self::assertSame(1, $history->getAccountId());
        self::assertSame('Google', $history->getName());
        self::assertSame('admin', $history->getLogin());
    }

    #[Test]
    public function searchFiltersByNameAndPaginates(): void
    {
        $all = $this->accountHistory->search(new ItemSearchDto('Google'));

        self::assertSame(5, $all->getNumRows());
        self::assertSame(5, $all->getTotalNumRows());

        $page = $this->accountHistory->search(new ItemSearchDto('Google', 1, 2));

        self::assertSame(2, $page->getNumRows());
        self::assertSame(5, $page->getTotalNumRows());
        self::assertSame(6, (int)$page->getDataAsArray()[0]->id);
    }

    #[Test]
    public function createGetHistoryDeleteAndUpdatePasswordRoundTrip(): void
    {
        $accountModel = new \SP\Domain\Account\Models\Account([
            'id' => 2,
            'userId' => 1,
            'userGroupId' => 1,
            'userEditId' => 1,
            'clientId' => 1,
            'categoryId' => 1,
            'name' => 'Repo History Target',
            'login' => 'hist_user',
            'url' => 'https://hist.example.test',
            'pass' => 'enc_pass_1',
            'key' => 'enc_key_1',
            'notes' => 'hist notes',
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
            'passDate' => 1700000000,
            'passDateChange' => 0,
            'parentId' => 0,
            'countView' => 0,
            'countDecrypt' => 0,
            'dateAdd' => '2026-01-01 00:00:00',
            'dateEdit' => '2026-01-02 00:00:00',
            'otherUserEdit' => 0,
            'otherUserGroupEdit' => 0,
        ]);

        $createdId = $this->accountHistory->create(
            new \SP\Domain\Account\Dtos\AccountHistoryCreateDto($accountModel, true, false, 'mhash1')
        );
        self::assertGreaterThan(7, $createdId);

        // getHistoryForAccount must match AccountHistory.accountId = 2 (including newly created row and id=3), NOT id=2
        $forAccount2 = $this->accountHistory->getHistoryForAccount(2)->getDataAsArray();
        $idsForAccount2 = array_map(static fn($r): int => (int)$r->id, $forAccount2);
        self::assertSame($createdId, $idsForAccount2[0]);
        self::assertContains(3, $idsForAccount2);

        $updatedPass = new \SP\Domain\Account\Dtos\EncryptedPassword('enc_pass_2', 'enc_key_2', 'mhash2');
        self::assertTrue($this->accountHistory->updatePassword($createdId, $updatedPass));

        $passRows = $this->accountHistory->getAccountsPassData()->getDataAsArray();
        $matchedPassRow = array_values(array_filter($passRows, static fn($r): bool => (int)$r->id === $createdId));
        self::assertCount(1, $matchedPassRow);
        self::assertSame('mhash2', $matchedPassRow[0]->mPassHash);

        self::assertTrue($this->accountHistory->delete($createdId));
        self::assertSame(0, $this->accountHistory->getById($createdId)->getNumRows());

        $batchA = $this->accountHistory->create(
            new \SP\Domain\Account\Dtos\AccountHistoryCreateDto($accountModel, true, false, 'mhashA')
        );
        $batchB = $this->accountHistory->create(
            new \SP\Domain\Account\Dtos\AccountHistoryCreateDto($accountModel, false, true, 'mhashB')
        );
        self::assertSame(2, $this->accountHistory->deleteByIdBatch([$batchA, $batchB]));

        $this->accountHistory->create(
            new \SP\Domain\Account\Dtos\AccountHistoryCreateDto($accountModel, true, false, 'mhashC')
        );
        self::assertGreaterThanOrEqual(1, $this->accountHistory->deleteByAccountIdBatch([2]));
        self::assertSame([], $this->accountHistory->getHistoryForAccount(2)->getDataAsArray());
    }

    #[Test]
    public function getByIdsGetByAccountIdAndReassignAccountIdWork(): void
    {
        $base = [
            'userId' => 1,
            'userGroupId' => 1,
            'userEditId' => 1,
            'clientId' => 1,
            'categoryId' => 1,
            'login' => 'r_user',
            'url' => 'https://r.test',
            'pass' => 'p',
            'key' => 'k',
            'notes' => '',
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
            'passDate' => 1700000000,
            'passDateChange' => 0,
            'parentId' => 0,
            'countView' => 0,
            'countDecrypt' => 0,
            'dateEdit' => '2026-02-02 00:00:00',
            'otherUserEdit' => 0,
            'otherUserGroupEdit' => 0,
        ];

        $hOld = $this->accountHistory->create(
            new \SP\Domain\Account\Dtos\AccountHistoryCreateDto(
                new \SP\Domain\Account\Models\Account(array_merge($base, [
                    'id' => 777,
                    'name' => 'Reassign Target',
                    'dateAdd' => '2026-02-01 00:00:00',
                ])),
                false,
                true,
                'mh1'
            )
        );
        $hOther = $this->accountHistory->create(
            new \SP\Domain\Account\Dtos\AccountHistoryCreateDto(
                new \SP\Domain\Account\Models\Account(array_merge($base, [
                    'id' => 777,
                    'name' => 'Collision Occupant',
                    'dateAdd' => '2026-03-01 00:00:00',
                ])),
                true,
                false,
                'mh2'
            )
        );

        $byIds = $this->accountHistory->getByIds([$hOld, $hOther, $hOld])->getDataAsArray();
        self::assertCount(2, $byIds);
        self::assertSame([], $this->accountHistory->getByIds([])->getDataAsArray());

        $byAccount = $this->accountHistory->getByAccountId(777)->getDataAsArray();
        self::assertSame([$hOther, $hOld], array_map(static fn($r): int => (int)$r->getId(), $byAccount));

        $moved = $this->accountHistory->reassignAccountId(777, 888, '2026-02-01 00:00:00');
        self::assertSame(1, $moved);

        $movedRow = $this->accountHistory->getById($hOld)->getData(AccountHistoryModel::class);
        $keptRow = $this->accountHistory->getById($hOther)->getData(AccountHistoryModel::class);
        self::assertSame(888, $movedRow->getAccountId());
        self::assertSame(777, $keptRow->getAccountId());
    }
}
