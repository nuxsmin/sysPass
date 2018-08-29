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

use SP\Core\Exceptions\ConstraintException;
use SP\DataModel\AccountDefaultPermissionData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Account\AccountDefaultPermissionRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountDefaultPermissionRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class AccountDefaultPermissionRepositoryTest extends DatabaseTestCase
{
    /**
     * @var AccountDefaultPermissionRepository
     */
    private static $repository;

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_accountDefaultPermission.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(AccountDefaultPermissionRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(3, self::$repository->deleteByIdBatch([1, 2, 3, 10]));

        $this->assertEquals(2, $this->conn->getRowCount('AccountDefaultPermission'));

        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(3));

        $this->assertEquals(1, self::$repository->delete(4));

        $this->assertEquals(0, self::$repository->delete(10));

        $this->assertEquals(3, $this->conn->getRowCount('AccountDefaultPermission'));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByIdBatch()
    {
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 3])->getDataAsArray());
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 5, 10])->getDataAsArray());
        $this->assertCount(0, self::$repository->getByIdBatch([])->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $data = new AccountDefaultPermissionData();
        $data->id = 1;
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 1;
        $data->permission = 'data';

        self::$repository->update($data);

        $this->assertEquals($data, self::$repository->getById(1)->getData());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateDuplicatedHash()
    {
        $this->expectException(ConstraintException::class);

        $data = new AccountDefaultPermissionData();
        $data->id = 1;
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 10;
        $data->permission = 'data';

        self::$repository->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateUnknownUserId()
    {
        $this->expectException(ConstraintException::class);

        $data = new AccountDefaultPermissionData();
        $data->id = 2;
        $data->userId = 10;

        self::$repository->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateUnknownUserGroupId()
    {
        $this->expectException(ConstraintException::class);

        $data = new AccountDefaultPermissionData();
        $data->id = 2;
        $data->userGroupId = 10;

        self::$repository->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateUnknownUserProfileId()
    {
        $this->expectException(ConstraintException::class);

        $data = new AccountDefaultPermissionData();
        $data->id = 2;
        $data->userProfileId = 10;

        self::$repository->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateUnknownId()
    {
        $data = new AccountDefaultPermissionData();
        $data->id = 10;
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 1;
        $data->permission = 'data';

        self::$repository->update($data);

        $this->assertEquals(0, self::$repository->update($data));
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetById()
    {
        $data = new AccountDefaultPermissionData();
        $data->id = 1;
        $data->userId = 1;
        $data->fixed = 0;
        $data->priority = 0;

        $result = self::$repository->getById(1);

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals($data, $result->getData());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetAll()
    {
        $count = $this->conn->getRowCount('AccountDefaultPermission');

        $result = self::$repository->getAll();
        $this->assertEquals($count, $result->getNumRows());

        /** @var AccountDefaultPermissionData[] $data */
        $data = $result->getDataAsArray();
        $this->assertCount($count, $data);

        $this->assertInstanceOf(AccountDefaultPermissionData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals(1, $data[0]->getUserId());
        $this->assertNull($data[0]->getUserGroupId());
        $this->assertNull($data[0]->getUserProfileId());
        $this->assertNull($data[0]->getPermission());
        $this->assertEquals(0, $data[0]->getFixed());
        $this->assertEquals(0, $data[0]->getPriority());

        $this->assertInstanceOf(AccountDefaultPermissionData::class, $data[1]);
        $this->assertEquals(2, $data[1]->getId());

        $this->assertInstanceOf(AccountDefaultPermissionData::class, $data[2]);
        $this->assertEquals(3, $data[2]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testSearch()
    {
        // Search for user's name
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('demo');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(\stdClass::class, $data[0]);
        $this->assertEquals(4, $data[0]->id);
        $this->assertEquals(2, $data[0]->userId);
        $this->assertNull($data[0]->userGroupId);
        $this->assertNull($data[0]->userProfileId);
        $this->assertNull($data[0]->permission);
        $this->assertEquals(0, $data[0]->fixed);
        $this->assertEquals(0, $data[0]->priority);
        $this->assertEquals('sysPass demo', $data[0]->userName);

        // Search for group's name
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('admins');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(\stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertNull($data[0]->userId);
        $this->assertEquals(1, $data[0]->userGroupId);
        $this->assertNull($data[0]->userProfileId);
        $this->assertNull($data[0]->permission);
        $this->assertEquals(0, $data[0]->fixed);
        $this->assertEquals(10, $data[0]->priority);
        $this->assertEquals('Admins', $data[0]->userGroupName);

        // Search for profile's name
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Usuarios');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(\stdClass::class, $data[0]);
        $this->assertEquals(5, $data[0]->id);
        $this->assertNull($data[0]->userId);
        $this->assertNull($data[0]->userGroupId);
        $this->assertEquals(3, $data[0]->userProfileId);
        $this->assertNull($data[0]->permission);
        $this->assertEquals(0, $data[0]->fixed);
        $this->assertEquals(10, $data[0]->priority);
        $this->assertEquals('Usuarios', $data[0]->userProfileName);

        // Search for no results
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('test_permission');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreate()
    {
        $data = new AccountDefaultPermissionData();
        $data->id = 6;
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 20;
        $data->permission = 'data';

        $id = self::$repository->create($data);

        $this->assertEquals($data->id, $id);
        $this->assertEquals($data, self::$repository->getById($id)->getData());
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreateDuplicatedHash()
    {
        $this->expectException(ConstraintException::class);

        $data = new AccountDefaultPermissionData();
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 10;
        $data->permission = 'data';

        self::$repository->create($data);
    }

    /**
     * @dataProvider userDataProvider
     *
     * @param int $userId
     * @param int $userGroupId
     * @param int $userProfileId
     * @param int $expected
     *
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByFilter($userId, $userGroupId, $userProfileId, $expected)
    {
        $result = self::$repository->getByFilter($userId, $userGroupId, $userProfileId);

        $this->assertEquals(1, $result->getNumRows());

        /** @var AccountDefaultPermissionData $data */
        $data = $result->getData();

        $this->assertInstanceOf(AccountDefaultPermissionData::class, $data);
        $this->assertEquals($expected, $data->getId());
    }

    /**
     * @return array
     */
    public function userDataProvider()
    {
        return [
            [1, 1, 1, 3],
            [1, 2, 2, 1],
            [1, 1, 3, 5],
            [2, 2, 2, 4],
            [2, 2, 3, 5],
            [2, 1, 3, 5],
            [3, 1, 1, 3],
            [3, 1, 2, 2],
        ];
    }
}
