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
use SP\Infrastructure\Account\Repositories\AccountToTag;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the account/tag relation repository.
 */
#[Group('integration')]
final class AccountToTagDbTest extends DatabaseRepositoryTestCase
{
    private AccountToTag $accountToTag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToTag = new AccountToTag(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getTagsByAccountIdReturnsTheTagsOrderedByName(): void
    {
        $tags = $this->accountToTag->getTagsByAccountId(1)->getDataAsArray(Simple::class);

        self::assertSame(
            ['windows', 'www'],
            array_map(static fn(Simple $tag) => $tag->name, $tags)
        );
        self::assertSame([2, 1], array_map(static fn(Simple $tag) => (int)$tag->id, $tags));
    }

    #[Test]
    public function getTagsByAccountIdReturnsNoRowsForAnAccountWithoutTags(): void
    {
        self::assertSame(0, $this->accountToTag->getTagsByAccountId(4)->getNumRows());
    }

    #[Test]
    public function addAppendsTheTagsToTheAccount(): void
    {
        $this->accountToTag->add(2, [2]);

        self::assertSame(
            ['Linux', 'windows', 'www'],
            array_map(
                static fn(Simple $tag) => $tag->name,
                $this->accountToTag->getTagsByAccountId(2)->getDataAsArray(Simple::class)
            )
        );
    }

    #[Test]
    public function deleteByAccountIdRemovesEveryTagOfTheAccountAndReportsSuccess(): void
    {
        self::assertTrue($this->accountToTag->deleteByAccountId(1));

        self::assertSame(0, $this->accountToTag->getTagsByAccountId(1)->getNumRows());
        self::assertSame(2, $this->accountToTag->getTagsByAccountId(2)->getNumRows());
    }

    #[Test]
    public function deleteByAccountIdReturnsFalseWhenThereIsNothingToDelete(): void
    {
        self::assertFalse($this->accountToTag->deleteByAccountId(4));
    }
}
