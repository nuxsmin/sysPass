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
}
