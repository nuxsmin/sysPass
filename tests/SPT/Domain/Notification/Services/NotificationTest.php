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

namespace SPT\Domain\Notification\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Domain\Notification\Ports\NotificationRepository;
use SP\Domain\Notification\Services\Notification;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\NotificationDataGenerator;
use SPT\Generators\UserDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class NotificationTest
 */
#[Group('unitary')]
class NotificationTest extends UnitaryTestCase
{

    private NotificationRepository|MockObject $notificationRepository;
    private Notification                      $notification;

    public function testSearchForUserId()
    {
        $itemSearchData = new ItemSearchData();

        $this->notificationRepository
            ->expects($this->once())
            ->method('searchForUserId')
            ->with($itemSearchData, 100);

        $this->notification->searchForUserId($itemSearchData, 100);
    }

    /**
     * @throws Exception
     */
    public function testGetAll()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(NotificationModel::class)
                    ->willReturn([1]);

        $this->notificationRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($queryResult);

        $out = $this->notification->getAll();

        $this->assertEquals([1], $out);
    }

    public function testSearchWithAdmin()
    {
        $userDataDto = new UserDataDto(
            UserDataGenerator::factory()
                             ->buildUserData()
                             ->mutate(
                                 [
                                     'isAdminApp' => true,
                                 ]
                             )
        );

        $this->context->setUserData($userDataDto);

        $itemSearchData = new ItemSearchData();

        $this->notificationRepository
            ->expects($this->once())
            ->method('searchForAdmin')
            ->with($itemSearchData, $userDataDto->getId());

        $this->notification->search($itemSearchData);
    }

    public function testSearchWithNoAdmin()
    {
        $userData = $this->context->getUserData();

        $itemSearchData = new ItemSearchData();

        $this->notificationRepository
            ->expects($this->once())
            ->method('searchForUserId')
            ->with($itemSearchData, $userData->getId());

        $this->notification->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testSetCheckedById()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('setCheckedById')
            ->with(100)
            ->willReturn(1);

        $this->notification->setCheckedById(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testSetCheckedByIdWithException()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('setCheckedById')
            ->with(100)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Notification not found');

        $this->notification->setCheckedById(100);
    }

    /**
     * @throws Exception
     */
    public function testGetAllActiveForCurrentUserWithAdmin()
    {
        $userDataDto = new UserDataDto(
            UserDataGenerator::factory()
                             ->buildUserData()
                             ->mutate(
                                 [
                                     'isAdminApp' => true,
                                 ]
                             )
        );
        $this->context->setUserData($userDataDto);

        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(NotificationModel::class)
                    ->willReturn([1]);

        $this->notificationRepository
            ->expects($this->once())
            ->method('getAllActiveForAdmin')
            ->with($userDataDto->getId())
            ->willReturn($queryResult);

        $out = $this->notification->getAllActiveForCurrentUser();

        $this->assertEquals([1], $out);
    }

    /**
     * @throws Exception
     */
    public function testGetAllActiveForCurrentUserWithNoAdmin()
    {
        $userData = $this->context->getUserData();

        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(NotificationModel::class)
                    ->willReturn([1]);

        $this->notificationRepository
            ->expects($this->once())
            ->method('getAllActiveForUserId')
            ->with($userData->getId())
            ->willReturn($queryResult);

        $out = $this->notification->getAllActiveForCurrentUser();

        $this->assertEquals([1], $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $notification = NotificationDataGenerator::factory()->buildNotification();

        $this->notificationRepository
            ->expects($this->once())
            ->method('create')
            ->with($notification)
            ->willReturn(new QueryResult(null, 0, 100));

        $out = $this->notification->create($notification);

        $this->assertEquals(100, $out);
    }

    /**
     * @throws Exception
     */
    public function testGetAllForUserId()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(NotificationModel::class)
                    ->willReturn([1]);

        $this->notificationRepository
            ->expects($this->once())
            ->method('getAllForUserId')
            ->with(100)
            ->willReturn($queryResult);

        $out = $this->notification->getAllForUserId(100);

        $this->assertEquals([1], $out);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdBatch()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(NotificationModel::class)
                    ->willReturn([1]);

        $this->notificationRepository
            ->expects($this->once())
            ->method('getByIdBatch')
            ->with([100, 200, 300])
            ->willReturn($queryResult);

        $out = $this->notification->getByIdBatch([100, 200, 300]);

        $this->assertEquals([1], $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 1));

        $this->notification->delete(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteWithException()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Notification not found');


        $this->notification->delete(100);
    }

    /**
     * @throws Exception
     */
    public function testGetForUserIdByDate()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(NotificationModel::class)
                    ->willReturn([1]);

        $this->notificationRepository
            ->expects($this->once())
            ->method('getForUserIdByDate')
            ->with('test', 100)
            ->willReturn($queryResult);

        $out = $this->notification->getForUserIdByDate('test', 100);

        $this->assertEquals([1], $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $notification = NotificationDataGenerator::factory()->buildNotification();

        $this->notificationRepository
            ->expects($this->once())
            ->method('update')
            ->with($notification)
            ->willReturn(100);

        $out = $this->notification->update($notification);

        $this->assertEquals(100, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     */
    public function testGetById()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getNumRows')
                    ->willReturn(1);

        $notification = new NotificationModel();

        $queryResult->expects($this->once())
                    ->method('getData')
                    ->with(NotificationModel::class)
                    ->willReturn($notification);

        $this->notificationRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn($queryResult);

        $out = $this->notification->getById(100);

        $this->assertEquals($notification, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     */
    public function testGetByIdWithException()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getNumRows')
                    ->willReturn(0);

        $queryResult->expects($this->never())
                    ->method('getData');

        $this->notificationRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Notification not found');

        $this->notification->getById(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteAdmin()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('deleteAdmin')
            ->with(100)
            ->willReturn(new QueryResult(null, 1));

        $this->notification->deleteAdmin(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteAdminWithException()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('deleteAdmin')
            ->with(100)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Notification not found');

        $this->notification->deleteAdmin(100);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 3));

        $this->notification->deleteByIdBatch([100, 200, 300]);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithException()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 1));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the notifications');

        $this->notification->deleteByIdBatch([100, 200, 300]);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteAdminBatch()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('deleteAdminBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 3));

        $this->notification->deleteAdminBatch([100, 200, 300]);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteAdminBatchWithException()
    {
        $this->notificationRepository
            ->expects($this->once())
            ->method('deleteAdminBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 1));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the notifications');

        $this->notification->deleteAdminBatch([100, 200, 300]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationRepository = $this->createMock(NotificationRepository::class);

        $this->notification = new Notification($this->application, $this->notificationRepository);
    }
}
