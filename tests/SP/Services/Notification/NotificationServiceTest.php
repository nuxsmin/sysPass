<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Services\Notification;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Messages\NotificationMessage;
use SP\DataModel\ItemSearchData;
use SP\DataModel\NotificationData;
use SP\Repositories\NoSuchItemException;
use SP\Services\Notification\NotificationService;
use SP\Services\ServiceException;
use SP\Services\User\UserLoginResponse;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class NotificationServiceTest
 *
 * @package SP\Tests\Services\Notification
 */
class NotificationServiceTest extends DatabaseTestCase
{
    /**
     * @var ContextInterface
     */
    private static $context;
    /**
     * @var NotificationService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_notification.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        self::$context = $dic->get(ContextInterface::class);

        // Inicializar el servicio
        self::$service = $dic->get(NotificationService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $data = self::$service->getAll();

        $this->assertCount(3, $data);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(3, $data[2]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearchForUserId()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Test');

        $result = self::$service->searchForUserId($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        $itemSearchData->setSeachString('Accounts');

        $result = self::$service->searchForUserId($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[1]->getId());

        $itemSearchData->setSeachString('Admins');

        $result = self::$service->searchForUserId($itemSearchData, 2);

        $this->assertEquals(0, $result->getNumRows());

        $itemSearchData->setSeachString('Global');

        $result = self::$service->searchForUserId($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        $itemSearchData->setSeachString('');

        $result = self::$service->searchForUserId($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[1]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetAllActiveForNonAdmin()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);
        $userData->setIsAdminApp(false);

        self::$context->setUserData($userData);

        $data = self::$service->getAllActiveForUserId(2);

        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[1]->getId());

        $data = self::$service->getAllActiveForUserId(3);

        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        self::$service->setCheckedById(1);

        $data = self::$service->getAllActiveForUserId(2);

        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        self::$service->setCheckedById(2);

        $this->assertCount(0, self::$service->getAllActiveForUserId(2));
        $this->assertCount(0, self::$service->getAllActiveForUserId(3));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetAllActiveForAdmin()
    {
        $userData = new UserLoginResponse();
        $userData->setId(1);
        $userData->setIsAdminApp(true);

        self::$context->setUserData($userData);

        $data = self::$service->getAllActiveForUserId(1);

        $this->assertCount(2, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());

        $data = self::$service->getAllActiveForUserId(3);

        $this->assertCount(2, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());

        self::$service->setCheckedById(2);

        $data = self::$service->getAllActiveForUserId(1);

        $this->assertCount(1, $data);
        $this->assertEquals(3, $data[0]->getId());

        self::$service->setCheckedById(3);

        $this->assertCount(0, self::$service->getAllActiveForUserId(1));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testSetCheckedById()
    {
        self::$service->setCheckedById(1);

        $this->assertTrue(true);

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals(0, self::$service->setCheckedById(4));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetForUserIdByDate()
    {
        // Forces an update of the notification's date field
        $this->assertEquals(1, self::$service->update(self::$service->getById(1)));

        $data = self::$service->getForUserIdByDate('Accounts', 2);

        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]->getId());

        $this->assertCount(0, self::$service->getForUserIdByDate('Accounts', 3));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testCreate()
    {
        $data = new NotificationData();
        $data->setId(4);
        $data->setUserId(2);
        $data->setType('Test');
        $data->setComponent('Config');
        $data->setDescription(NotificationMessage::factory()->setTitle('Prueba')->setDescription(['blablabla']));
        $data->setChecked(0);
        $data->setOnlyAdmin(1);
        $data->setSticky(1);

        $this->assertEquals(4, self::$service->create($data));

        $resultData = self::$service->getById(4);

        $this->assertEquals($data->getId(), $resultData->getId());
        $this->assertEquals($data->getUserId(), $resultData->getUserId());
        $this->assertEquals($data->getType(), $resultData->getType());
        $this->assertEquals($data->getComponent(), $resultData->getComponent());
        $this->assertEquals($data->isChecked(), $resultData->isChecked());
        $this->assertEquals($data->isOnlyAdmin(), $resultData->isOnlyAdmin());
        $this->assertEquals($data->isSticky(), $resultData->isSticky());
        $this->assertGreaterThan(0, $resultData->getDate());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $this->assertCount(0, self::$service->getByIdBatch([]));

        $data = self::$service->getByIdBatch([1, 2, 3, 4]);

        $this->assertCount(3, $data);
        $this->assertInstanceOf(NotificationData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals('Prueba', $data[0]->getType());
        $this->assertEquals('Accounts', $data[0]->getComponent());
        $this->assertEquals('Notificación de prueba', trim($data[0]->getDescription()));
        $this->assertEquals(1529145158, $data[0]->getDate());
        $this->assertEquals(0, $data[0]->isChecked());
        $this->assertEquals(0, $data[0]->isOnlyAdmin());
        $this->assertEquals(0, $data[0]->isSticky());
        $this->assertEquals(2, $data[0]->getUserId());

        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(3, $data[2]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearchNonAdmin()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);
        $userData->setIsAdminApp(false);

        self::$context->setUserData($userData);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Test');

        $result = self::$service->search($itemSearchData);

        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());

        $this->assertCount(1, $data);
        $this->assertInstanceOf(NotificationData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals('test', $data[0]->getDescription());

        $itemSearchData->setSeachString('Global');

        $result = self::$service->search($itemSearchData);

        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(NotificationData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals('Global', $data[0]->getType());

        $itemSearchData->setSeachString('');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $result->getDataAsArray());

        $itemSearchData->setSeachString('Accounts');

        $result = self::$service->search($itemSearchData);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals('Accounts', $data[0]->getComponent());
        $this->assertEquals(1, $data[1]->getId());
        $this->assertEquals('Accounts', $data[1]->getComponent());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearchAdmin()
    {
        $userData = new UserLoginResponse();
        $userData->setId(1);
        $userData->setIsAdminApp(true);

        self::$context->setUserData($userData);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Test');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(2, $result->getNumRows());

        $itemSearchData->setSeachString('Global');

        $result = self::$service->search($itemSearchData);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(NotificationData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals('Global', $data[0]->getType());

        $itemSearchData->setSeachString('');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $result->getDataAsArray());

        $itemSearchData->setSeachString('Accounts');

        $result = self::$service->search($itemSearchData);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals('Accounts', $data[0]->getComponent());
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals('Accounts', $data[1]->getComponent());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetById()
    {
        $data = self::$service->getById(3);

        $this->assertInstanceOf(NotificationData::class, $data);
        $this->assertEquals(3, $data->getId());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(4);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteAdminBatch()
    {
        $this->assertEquals(3, self::$service->deleteAdminBatch([1, 2, 3]));

        $this->assertEquals(0, self::$service->deleteAdminBatch([]));

        $this->assertEquals(0, $this->conn->getRowCount('Notification'));

        $this->expectException(ServiceException::class);

        $this->assertEquals(2, self::$service->deleteAdminBatch([4]));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteAdmin()
    {
        $countBefore = $this->conn->getRowCount('Notification');

        self::$service->deleteAdmin(3);

        $this->assertEquals($countBefore - 1, $this->conn->getRowCount('Notification'));

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals(1, self::$service->deleteAdmin(4));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllForUserId()
    {
        $data = self::$service->getAllForUserId(2);

        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[1]->getId());

        $this->assertCount(1, self::$service->getAllForUserId(3));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $data = new NotificationData();
        $data->setId(3);
        $data->setUserId(2);
        $data->setType('Test');
        $data->setComponent('Config');
        $data->setDescription(NotificationMessage::factory()->setTitle('Prueba')->setDescription(['blablabla']));
        $data->setChecked(0);
        $data->setOnlyAdmin(1);
        $data->setSticky(1);

        $this->assertEquals(1, self::$service->update($data));

        $resultData = self::$service->getById(3);

        $this->assertEquals($data->getId(), $resultData->getId());
        $this->assertEquals($data->getUserId(), $resultData->getUserId());
        $this->assertEquals($data->getType(), $resultData->getType());
        $this->assertEquals($data->getComponent(), $resultData->getComponent());
        $this->assertEquals($data->isChecked(), $resultData->isChecked());
        $this->assertEquals($data->isOnlyAdmin(), $resultData->isOnlyAdmin());
        $this->assertEquals($data->isSticky(), $resultData->isSticky());
        $this->assertGreaterThan(0, $resultData->getDate());

        $data->setId(4);

        $this->assertEquals(0, self::$service->update($data));

        // FIXME: No exception on Travis CI??
//        $data = new NotificationData();
//        $data->setId(1);
//
//        $this->expectException(ConstraintException::class);
//
//        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        self::$service->delete(3);

        $this->assertEquals(2, $this->conn->getRowCount('Notification'));

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals(0, self::$service->delete(4));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$service->deleteByIdBatch([1, 3]));

        $this->assertEquals(0, self::$service->deleteByIdBatch([]));

        $this->assertEquals(1, $this->conn->getRowCount('Notification'));

        $this->expectException(ServiceException::class);

        $this->assertEquals(2, self::$service->deleteByIdBatch([2]));
    }
}
