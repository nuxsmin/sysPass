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

namespace SP\Tests\Domain\User\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\UserGroup as UserGroupModel;
use SP\Domain\User\Ports\UserGroupRepository;
use SP\Domain\User\Ports\UserToUserGroupService;
use SP\Domain\User\Services\UserGroup;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\UserGroupGenerator;
use SP\Tests\Stubs\UserGroupRepositoryStub;
use SP\Tests\UnitaryTestCase;

/**
 * Class UserGroupTest
 */
#[Group('unitary')]
class UserGroupTest extends UnitaryTestCase
{

    private MockObject|UserGroupRepository    $userGroupRepository;
    private UserToUserGroupService|MockObject $userToUserGroupService;
    private UserGroup                         $userGroup;

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $this->userGroupRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn(new QueryResult([new UserGroupModel()]));

        $this->userToUserGroupService
            ->expects($this->once())
            ->method('getUsersByGroupId')
            ->with(100)
            ->willReturn([1, 2, 3]);

        $out = $this->userGroup->getById(100);

        $this->assertEquals([1, 2, 3], $out->getUsers());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetByIdWithNoGroup()
    {
        $this->userGroupRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn(new QueryResult([]));

        $this->userToUserGroupService
            ->expects($this->never())
            ->method('getUsersByGroupId');

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Group not found');

        $this->userGroup->getById(100);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsage()
    {
        $queryResult = new QueryResult([new UserGroupModel()]);

        $this->userGroupRepository
            ->expects($this->once())
            ->method('getUsage')
            ->with(100)
            ->willReturn($queryResult);

        $out = $this->userGroup->getUsage(100);

        $this->assertEquals($queryResult->getDataAsArray(), $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageByUsers()
    {
        $queryResult = new QueryResult([new UserGroupModel()]);

        $this->userGroupRepository
            ->expects($this->once())
            ->method('getUsageByUsers')
            ->with(100)
            ->willReturn($queryResult);

        $out = $this->userGroup->getUsageByUsers(100);

        $this->assertEquals($queryResult->getDataAsArray(), $out);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdate()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData();

        $this->userGroupRepository
            ->expects($this->once())
            ->method('update')
            ->with($userGroup);

        $this->userToUserGroupService
            ->expects($this->once())
            ->method('update')
            ->with($userGroup->getId(), $userGroup->getUsers());

        $this->userGroup->update($userGroup);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateWithNoUsers()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData()->mutate(['users' => null]);

        $this->userGroupRepository
            ->expects($this->once())
            ->method('update')
            ->with($userGroup);

        $this->userToUserGroupService
            ->expects($this->never())
            ->method('update');

        $this->userGroup->update($userGroup);
    }

    /**
     * @throws NoSuchItemException
     */
    public function testGetByName()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData();

        $this->userGroupRepository
            ->expects($this->once())
            ->method('getByName')
            ->with('a_group')
            ->willReturn(new QueryResult([$userGroup]));

        $out = $this->userGroup->getByName('a_group');

        $this->assertEquals($userGroup, $out);
    }

    /**
     * @throws NoSuchItemException
     */
    public function testGetByNameWithNoGroup()
    {
        $this->userGroupRepository
            ->expects($this->once())
            ->method('getByName')
            ->with('a_group')
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Group not found');

        $this->userGroup->getByName('a_group');
    }

    /**
     * @throws ServiceException
     */
    public function testCreate()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData();

        $this->userGroupRepository
            ->expects($this->once())
            ->method('create')
            ->with($userGroup)
            ->willReturn(new QueryResult([$userGroup], 1, 100));

        $this->userToUserGroupService
            ->expects($this->once())
            ->method('add')
            ->with(100, $userGroup->getUsers());

        $out = $this->userGroup->create($userGroup);

        $this->assertEquals(100, $out);
    }

    /**
     * @throws ServiceException
     */
    public function testCreateWithNoUsers()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData()->mutate(['users' => null]);

        $this->userGroupRepository
            ->expects($this->once())
            ->method('create')
            ->with($userGroup)
            ->willReturn(new QueryResult([$userGroup], 1, 100));

        $this->userToUserGroupService
            ->expects($this->never())
            ->method('add');

        $out = $this->userGroup->create($userGroup);

        $this->assertEquals(100, $out);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->userGroupRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 3));

        $out = $this->userGroup->deleteByIdBatch([100, 200, 300]);

        $this->assertEquals(3, $out);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithException()
    {
        $this->userGroupRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 1));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the groups');

        $this->userGroup->deleteByIdBatch([100, 200, 300]);
    }


    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->userGroupRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 1));

        $this->userGroup->delete(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteWithException()
    {
        $this->userGroupRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult());

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Group not found');

        $this->userGroup->delete(100);
    }

    public function testSearch()
    {
        $itemSearchData = new ItemSearchDto('test', 1, 10);

        $queryResult = new QueryResult([1]);

        $this->userGroupRepository
            ->expects($this->once())
            ->method('search')
            ->with($itemSearchData)
            ->willReturn($queryResult);

        $out = $this->userGroup->search($itemSearchData);

        $this->assertEquals($queryResult, $out);
    }

    public function testGetAll()
    {
        $queryResult = new QueryResult([UserGroupGenerator::factory()->buildUserGroupData()]);

        $this->userGroupRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($queryResult);

        $out = $this->userGroup->getAll();

        $this->assertEquals($queryResult->getDataAsArray(), $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $userGroupRepositoryMethods = array_filter(
            get_class_methods(UserGroupRepositoryStub::class),
            static fn(string $method) => $method != 'transactionAware'
        );

        $this->userGroupRepository = $this->createPartialMock(
            UserGroupRepositoryStub::class,
            $userGroupRepositoryMethods
        );
        $this->userToUserGroupService = $this->createMock(UserToUserGroupService::class);

        $this->userGroup = new UserGroup($this->application, $this->userGroupRepository, $this->userToUserGroupService);
    }


}
