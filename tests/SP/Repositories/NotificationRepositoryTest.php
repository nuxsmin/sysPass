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

namespace SP\Tests\Repositories;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Messages\NotificationMessage;
use SP\DataModel\ItemSearchData;
use SP\DataModel\NotificationData;
use SP\Repositories\Notification\NotificationRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class NotificationRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class NotificationRepositoryTest extends DatabaseTestCase
{
    /**
     * @var NotificationRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(NotificationRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteAdmin()
    {
        $countBefore = $this->conn->getRowCount('Notification');

        $this->assertEquals(1, self::$repository->deleteAdmin(3));
        $this->assertEquals($countBefore - 1, $this->conn->getRowCount('Notification'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteAdminBatch()
    {
        $this->assertEquals(3, self::$repository->deleteAdminBatch([1, 2, 3, 5]));
        $this->assertEquals(0, $this->conn->getRowCount('Notification'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $this->assertEquals(0, self::$repository->getByIdBatch([])->getNumRows());

        $result = self::$repository->getByIdBatch([1, 2, 3, 4]);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

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
    public function testGetById()
    {
        $result = self::$repository->getById(3);
        /** @var NotificationData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(NotificationData::class, $data);
        $this->assertEquals(3, $data->getId());

        $result = self::$repository->getById(4);
        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
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

        $this->assertEquals(4, self::$repository->create($data)->getLastId());

        /** @var NotificationData $resultData */
        $resultData = self::$repository->getById(4)->getData();

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
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Test');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(2, $result->getNumRows());

        $itemSearchData->setSeachString('Global');

        $result = self::$repository->search($itemSearchData);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(NotificationData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals('Global', $data[0]->getType());

        $itemSearchData->setSeachString('');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $result->getDataAsArray());

        $itemSearchData->setSeachString('Accounts');

        $result = self::$repository->search($itemSearchData);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $data);
        $this->assertEquals(1529145313, $data[0]->getDate());
        $this->assertEquals('Accounts', $data[0]->getComponent());
        $this->assertEquals(1529145296, $data[1]->getDate());
        $this->assertEquals('Accounts', $data[1]->getComponent());
        $this->assertEquals(1529145158, $data[2]->getDate());
        $this->assertEquals('Accounts', $data[2]->getComponent());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSetCheckedById()
    {
        $this->assertEquals(1, self::$repository->setCheckedById(1));
        $this->assertEquals(0, self::$repository->setCheckedById(4));
    }

    /**
     * @throws ConstraintException
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

        $this->assertEquals(1, self::$repository->update($data));

        /** @var NotificationData $resultData */
        $resultData = self::$repository->getById($data->getId())->getData();

        $this->assertEquals($data->getId(), $resultData->getId());
        $this->assertEquals($data->getUserId(), $resultData->getUserId());
        $this->assertEquals($data->getType(), $resultData->getType());
        $this->assertEquals($data->getComponent(), $resultData->getComponent());
        $this->assertEquals($data->isChecked(), $resultData->isChecked());
        $this->assertEquals($data->isOnlyAdmin(), $resultData->isOnlyAdmin());
        $this->assertEquals($data->isSticky(), $resultData->isSticky());
        $this->assertGreaterThan(0, $resultData->getDate());

        $data->setId(4);

        $this->assertEquals(0, self::$repository->update($data));

        // FIXME: No exception on Travis CI??
//        $data = new NotificationData();
//        $data->setId(1);
//
//        $this->expectException(ConstraintException::class);
//
//        self::$repository->update($data);
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

        $result = self::$repository->searchForUserId($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        $itemSearchData->setSeachString('Accounts');

        $result = self::$repository->searchForUserId($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[1]->getId());

        $itemSearchData->setSeachString('Admins');

        $result = self::$repository->searchForUserId($itemSearchData, 2);

        $this->assertEquals(0, $result->getNumRows());

        $itemSearchData->setSeachString('Global');

        $result = self::$repository->searchForUserId($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        $itemSearchData->setSeachString('');

        $result = self::$repository->searchForUserId($itemSearchData, 2);
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
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();

        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $data);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(3, $data[2]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForUserIdByDate()
    {
        // Forces an update of the notification's date field
        $this->assertEquals(1, self::$repository->update(self::$repository->getById(1)->getData()));

        $result = self::$repository->getForUserIdByDate('Accounts', 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]->getId());

        $result = self::$repository->getForUserIdByDate('Accounts', 3);

        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(3));
        $this->assertEquals(0, self::$repository->delete(4));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllForUserId()
    {
        $result = self::$repository->getAllForUserId(2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[1]->getId());

        $result = self::$repository->getAllForUserId(3);

        $this->assertEquals(1, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$repository->deleteByIdBatch([1, 2, 3, 4]));
        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));

        $this->assertEquals(1, $this->conn->getRowCount('Notification'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllActiveForUserId()
    {
        $result = self::$repository->getAllActiveForUserId(2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[1]->getId());

        $result = self::$repository->getAllActiveForUserId(3);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        self::$repository->setCheckedById(1);

        $result = self::$repository->getAllActiveForUserId(2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        self::$repository->setCheckedById(2);

        $this->assertEquals(0, self::$repository->getAllActiveForUserId(2)->getNumRows());
        $this->assertEquals(0, self::$repository->getAllActiveForUserId(3)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllActiveForAdmin()
    {
        $result = self::$repository->getAllActiveForAdmin(1);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());

        $result = self::$repository->getAllActiveForAdmin(2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(1, $data[2]->getId());

        $result = self::$repository->getAllActiveForAdmin(3);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearchForAdmin()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Test');

        $result = self::$repository->searchForAdmin($itemSearchData, 1);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());

        $itemSearchData->setSeachString('Accounts');

        $result = self::$repository->searchForAdmin($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(1, $data[2]->getId());

        $itemSearchData->setSeachString('Admins');

        $result = self::$repository->searchForAdmin($itemSearchData, 2);

        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(3, $data[0]->getId());

        $itemSearchData->setSeachString('Global');

        $result = self::$repository->searchForAdmin($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->getId());

        $itemSearchData->setSeachString('');

        $result = self::$repository->searchForAdmin($itemSearchData, 2);
        /** @var NotificationData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertCount(3, $data);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(1, $data[2]->getId());
    }
}
