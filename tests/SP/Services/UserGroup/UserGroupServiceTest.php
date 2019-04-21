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

namespace SP\Tests\SP\Services\UserGroup;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\ServiceException;
use SP\Services\UserGroup\UserGroupService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class UserGroupServiceTest
 *
 * @package SP\Tests\SP\Services\UserGroup
 */
class UserGroupServiceTest extends DatabaseTestCase
{

    /**
     * @var UserGroupService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     * @throws SPException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_userGroup.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(UserGroupService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $data = self::$service->getAllBasic();

        $this->assertCount(5, $data);

        $this->assertInstanceOf(UserGroupData::class, $data[0]);
        $this->assertEquals('Admins', $data[0]->getName());
        $this->assertEquals('sysPass Admins', $data[0]->getDescription());

        $this->assertInstanceOf(UserGroupData::class, $data[1]);
        $this->assertEquals('Demo', $data[1]->getName());
        $this->assertEmpty($data[1]->getDescription());
    }

    /**
     * @throws SPException
     */
    public function testDelete()
    {
        self::$service->delete(3);

        $this->assertEquals(4, $this->conn->getRowCount('UserGroup'));
    }

    /**
     * @throws SPException
     */
    public function testDeleteUsed()
    {
        $this->expectException(ConstraintException::class);

        self::$service->delete(1);
    }

    /**
     * @throws SPException
     */
    public function testDeleteUnknown()
    {
        $this->expectException(NoSuchItemException::class);

        self::$service->delete(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$service->deleteByIdBatch([4, 5]));

        $this->assertEquals(3, $this->conn->getRowCount('UserGroup'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatchUsed()
    {
        // Se lanza excepción en caso de restricción relacional
        $this->expectException(ConstraintException::class);

        self::$service->deleteByIdBatch([1, 2]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatchUnknown()
    {
        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([4, 5, 10]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $data = new UserGroupData();
        $data->setId(2);
        $data->setName('Test group');
        $data->setDescription('Group for demo users');
        $data->setUsers([2]);

        self::$service->update($data);

        $this->assertEquals($data, self::$service->getById(2));
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateDuplicated()
    {
        $data = new UserGroupData();
        $data->setId(2);
        $data->setName('Admins');

        $this->expectException(DuplicatedItemException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsage()
    {
        $this->assertCount(7, self::$service->getUsage(2));

        $this->assertCount(1, self::$service->getUsage(3));

        $this->assertCount(0, self::$service->getUsage(4));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testCreate()
    {
        $data = new UserGroupData();
        $data->setId(6);
        $data->setName('Test group');
        $data->setDescription('Group for demo users');
        $data->setUsers([2]);

        $this->assertEquals($data->getId(), self::$service->create($data));

        $this->assertEquals($data, self::$service->getById($data->getId()));
    }

    /**
     * @throws ServiceException
     */
    public function testCreateDuplicated()
    {
        $data = new UserGroupData();
        $data->setName('Admins');
        $data->setDescription('Group for demo users');

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Demo');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(UserGroupData::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Demo', $data[0]->name);
        $this->assertEmpty($data[0]->description);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('test');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(2, $result->getNumRows());

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('aa');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $data = self::$service->getByName('Demo');

        $this->assertInstanceOf(UserGroupData::class, $data);
        $this->assertEquals('Demo', $data->getName());
        $this->assertEmpty($data->getDescription());

        $this->expectException(NoSuchItemException::class);

        self::$service->getByName('Test');
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $data = self::$service->getById(2);

        $this->assertInstanceOf(UserGroupData::class, $data);
        $this->assertEquals('Demo', $data->getName());
        $this->assertEmpty($data->getDescription());


        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageByUsers()
    {
        $this->assertCount(2, self::$service->getUsageByUsers(1));

        $this->assertCount(5, self::$service->getUsageByUsers(2));

        $this->assertCount(0, self::$service->getUsageByUsers(4));
    }
}
