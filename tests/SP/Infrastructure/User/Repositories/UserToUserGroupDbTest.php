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
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Infrastructure\User\Repositories\UserToUserGroup;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the user/group membership repository.
 */
#[Group('integration')]
final class UserToUserGroupDbTest extends DatabaseRepositoryTestCase
{
    private UserToUserGroup $userToUserGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userToUserGroup = new UserToUserGroup(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function checkUserInGroupMatchesTheMembership(): void
    {
        self::assertTrue($this->userToUserGroup->checkUserInGroup(2, 3));
        self::assertFalse($this->userToUserGroup->checkUserInGroup(1, 3));
    }

    #[Test]
    public function getGroupsForUserReturnsOnlyTheMembershipsOfTheUser(): void
    {
        $groups = $this->userToUserGroup->getGroupsForUser(2)
                                        ->getDataAsArray(UserToUserGroupModel::class);

        self::assertCount(1, $groups);
        self::assertSame(2, $groups[0]->getUserId());
        self::assertSame(1, $groups[0]->getUserGroupId());

        self::assertCount(
            0,
            $this->userToUserGroup->getGroupsForUser(5)->getDataAsArray(UserToUserGroupModel::class)
        );
    }

    #[Test]
    public function updateReplacesTheUsersOfTheGroup(): void
    {
        $this->userToUserGroup->update(2, [1, 2]);

        $groups = $this->userToUserGroup->getById(2)->getDataAsArray(UserToUserGroupModel::class);

        self::assertSame(
            [1, 2],
            array_map(static fn(UserToUserGroupModel $group) => $group->getUserId(), $groups)
        );

        self::assertFalse($this->userToUserGroup->checkUserInGroup(2, 3));
    }

    #[Test]
    public function updateRollsBackWhenTheNewMembershipCannotBeSaved(): void
    {
        try {
            $this->userToUserGroup->update(2, [3, 3]);

            self::fail('The duplicated membership should have failed');
        } catch (ConstraintException) {
            // Expected: the transaction must be rolled back
        }

        $groups = $this->userToUserGroup->getById(2)->getDataAsArray(UserToUserGroupModel::class);

        self::assertSame(
            [1, 3],
            array_map(static fn(UserToUserGroupModel $group) => $group->getUserId(), $groups)
        );
        self::assertTrue($this->userToUserGroup->checkUserInGroup(2, 3));
    }
}
