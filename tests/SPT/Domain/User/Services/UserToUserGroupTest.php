<?php
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

namespace SPT\Domain\User\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Domain\User\Ports\UserToUserGroupRepository;
use SP\Domain\User\Services\UserToUserGroup;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class UserToUserGroupTest
 */
#[Group('unitary')]
class UserToUserGroupTest extends UnitaryTestCase
{

    private UserToUserGroup                      $userToUserGroup;
    private UserToUserGroupRepository|MockObject $userToUserGroupRepository;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $this->userToUserGroupRepository
            ->expects($this->once())
            ->method('update')
            ->with(100, [1, 2, 3])
            ->willReturn(new QueryResult(null, 10));

        $this->assertEquals(10, $this->userToUserGroup->update(100, [1, 2, 3]));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateWithNoUsers()
    {
        $this->userToUserGroupRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 10));

        $this->assertEquals(10, $this->userToUserGroup->update(100, []));
    }

    public function testGetUsersByGroupId()
    {
        $userToUserGroup = new UserToUserGroupModel(
            [
                'userGroupId' => self::$faker->randomNumber(3),
                'userId' => self::$faker->randomNumber(3),
                'users' => [100, 200, 300]
            ]
        );

        $this->userToUserGroupRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn(new QueryResult([$userToUserGroup]));

        $out = $this->userToUserGroup->getUsersByGroupId(100);
        $this->assertEquals([$userToUserGroup->getUserId()], $out);
    }

    public function testGetGroupsForUser()
    {
        $userToUserGroup = new UserToUserGroupModel(
            [
                'userGroupId' => self::$faker->randomNumber(3),
                'userId' => self::$faker->randomNumber(3),
                'users' => [100, 200, 300]
            ]
        );

        $this->userToUserGroupRepository
            ->expects($this->once())
            ->method('getGroupsForUser')
            ->with(100)
            ->willReturn(new QueryResult([$userToUserGroup]));

        $out = $this->userToUserGroup->getGroupsForUser(100);
        $this->assertEquals([$userToUserGroup], $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $this->userToUserGroupRepository
            ->expects($this->once())
            ->method('add')
            ->with(100, [1, 2, 3])
            ->willReturn(new QueryResult(null, 0, 10));

        $this->assertEquals(10, $this->userToUserGroup->add(100, [1, 2, 3]));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userToUserGroupRepository = $this->createMock(UserToUserGroupRepository::class);

        $this->userToUserGroup = new UserToUserGroup($this->application, $this->userToUserGroupRepository);
    }
}
