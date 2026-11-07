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
use SP\Domain\Common\Models\Simple;
use SP\Infrastructure\Account\Repositories\AccountToFavorite;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the account/favorite relation repository.
 */
#[Group('integration')]
final class AccountToFavoriteDbTest extends DatabaseRepositoryTestCase
{
    private AccountToFavorite $accountToFavorite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToFavorite = new AccountToFavorite(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getForUserIdReturnsTheFavoritesOfTheUser(): void
    {
        self::assertSame(
            [[1, 3], [2, 3]],
            $this->pairsForUser(3)
        );

        self::assertSame(0, $this->accountToFavorite->getForUserId(5)->getNumRows());
    }

    #[Test]
    public function addCreatesTheFavoriteRelation(): void
    {
        $this->accountToFavorite->add(3, 1);

        self::assertSame([[1, 1], [3, 1]], $this->pairsForUser(1));
    }

    #[Test]
    public function deleteRemovesTheFavoriteRelationAndReportsSuccess(): void
    {
        self::assertTrue($this->accountToFavorite->delete(1, 3));

        self::assertSame([[2, 3]], $this->pairsForUser(3));
    }

    #[Test]
    public function deleteReturnsFalseWhenTheRelationDoesNotExist(): void
    {
        self::assertFalse($this->accountToFavorite->delete(1, 5));
    }

    /**
     * @return array<int, array{int, int}>
     */
    private function pairsForUser(int $userId): array
    {
        $pairs = array_map(
            static fn(Simple $favorite) => [(int)$favorite->accountId, (int)$favorite->userId],
            $this->accountToFavorite->getForUserId($userId)->getDataAsArray(Simple::class)
        );

        sort($pairs);

        return $pairs;
    }
}
