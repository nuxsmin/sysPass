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

namespace SP\Tests\Infrastructure\User\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Infrastructure\User\Repositories\UserGroup;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the UserGroup repository queries.
 */
#[Group('integration')]
final class UserGroupDbTest extends DatabaseRepositoryTestCase
{
    private UserGroup $userGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userGroup = new UserGroup($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function getUsageReturnsOneRowPerUsingItem(): void
    {
        $rows = $this->userGroup->getUsage(2)->getDataAsArray();

        $counts = array_count_values(array_map(
            static fn($row) => $row->ref,
            $rows
        ));

        self::assertSame(7, count($rows));
        self::assertSame(
            [
                'User' => 3,
                'UserGroup' => 2,
                'AccountToUserGroup' => 2,
            ],
            $counts
        );
    }

    #[Test]
    public function getUsageReturnsNoRowsForAnUnusedGroup(): void
    {
        self::assertSame(0, $this->userGroup->getUsage(5)->getNumRows());
    }

    #[Test]
    public function getUsageByUsersReturnsTheUsersLinkedToTheGroup(): void
    {
        $rows = $this->userGroup->getUsageByUsers(2)->getDataAsArray();

        $users = array_map(
            static fn($row) => [(int)$row->id, $row->ref],
            $rows
        );

        sort($users);

        self::assertSame(
            [
                [1, 'UserGroup'],
                [2, 'User'],
                [3, 'User'],
                [3, 'UserGroup'],
                [4, 'User'],
            ],
            $users
        );
    }
}
