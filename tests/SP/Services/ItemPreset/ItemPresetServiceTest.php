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

namespace SP\Tests\Services\ItemPreset;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPresetData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\NoSuchItemException;
use SP\Services\ItemPreset\ItemPresetRequest;
use SP\Services\ItemPreset\ItemPresetService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class ItemPresetServiceTest
 *
 * @package SP\Tests\Services\ItemPreset
 */
class ItemPresetServiceTest extends DatabaseTestCase
{
    /**
     * @var ItemPresetService
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

        self::$dataset = 'syspass_itemPreset.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(ItemPresetService::class);
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
     * @throws QueryException
     */
    public function testGetForUser($userId, $userGroupId, $userProfileId, $expected)
    {
        $result = self::$service->getForUser('permission', $userId, $userGroupId, $userProfileId);

        $this->assertInstanceOf(ItemPresetData::class, $result);
        $this->assertEquals($expected, $result->getId());
    }

    /**
     * @return array
     */
    public function userDataProvider()
    {
        return [
            [1, 1, 1, 3],
            [1, 2, 2, 1],
            [1, 1, 3, 2],
            [2, 2, 2, 2],
            [2, 2, 3, 2],
            [2, 1, 3, 2],
            [3, 1, 1, 3],
            [3, 1, 2, 2],
        ];
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetById()
    {
        $data = new ItemPresetData();
        $data->id = 1;
        $data->userId = 1;
        $data->fixed = 0;
        $data->priority = 0;
        $data->type = 'permission';

        $result = self::$service->getById(1);

        $this->assertInstanceOf(ItemPresetData::class, $result);
        $this->assertEquals($data, $result);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $count = $this->conn->getRowCount('ItemPreset');

        $result = self::$service->getAll();
        $this->assertCount($count, $result);

        $this->assertInstanceOf(ItemPresetData::class, $result[0]);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals('permission', $result[0]->getType());
        $this->assertEquals(1, $result[0]->getUserId());
        $this->assertNull($result[0]->getUserGroupId());
        $this->assertNull($result[0]->getUserProfileId());
        $this->assertNull($result[0]->getData());
        $this->assertEquals(0, $result[0]->getFixed());
        $this->assertEquals(0, $result[0]->getPriority());

        $this->assertInstanceOf(ItemPresetData::class, $result[1]);
        $this->assertEquals(2, $result[1]->getId());

        $this->assertInstanceOf(ItemPresetData::class, $result[2]);
        $this->assertEquals(3, $result[2]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws NoSuchPropertyException
     */
    public function testUpdate()
    {
        $accountPermission = new AccountPermission();
        $accountPermission->setUsersEdit([1, 2]);
        $accountPermission->setUsersView([3]);
        $accountPermission->setUserGroupsView([2]);
        $accountPermission->setUserGroupsEdit([1, 3]);

        $data = new ItemPresetData();
        $data->id = 1;
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 1;
        $data->type = 'permission';

        $request = new ItemPresetRequest($data, $accountPermission);

        self::$service->update($request);

        $resultData = self::$service->getById(1);

        $this->assertEquals($data, $resultData);
        $this->assertEquals($accountPermission, $resultData->hydrate(AccountPermission::class));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateUnKnown()
    {
        $accountPermission = new AccountPermission();
        $accountPermission->setUsersEdit([1, 2]);
        $accountPermission->setUsersView([3]);
        $accountPermission->setUserGroupsView([2]);
        $accountPermission->setUserGroupsEdit([1, 3]);

        $data = new ItemPresetData();
        $data->id = 10;
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 1;
        $data->type = 'permission';

        $request = new ItemPresetRequest($data, $accountPermission);

        $this->assertEquals(0, self::$service->update($request));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testDelete()
    {
        self::$service
            ->delete(3)
            ->delete(4);

        $this->assertEquals(3, $this->conn->getRowCount('ItemPreset'));
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteUnKnown()
    {
        $this->expectException(NoSuchItemException::class);

        $this->assertEquals(0, self::$service->delete(10));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        // Search for user's name
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('demo');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(4, $data[0]->id);
        $this->assertEquals('permission', $data[0]->type);
        $this->assertEquals(2, $data[0]->userId);
        $this->assertNull($data[0]->userGroupId);
        $this->assertNull($data[0]->userProfileId);
        $this->assertNull($data[0]->data);
        $this->assertEquals(0, $data[0]->fixed);
        $this->assertEquals(0, $data[0]->priority);
        $this->assertEquals('sysPass demo', $data[0]->userName);

        // Search for group's name
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('admins');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('permission', $data[0]->type);
        $this->assertNull($data[0]->userId);
        $this->assertEquals(1, $data[0]->userGroupId);
        $this->assertNull($data[0]->userProfileId);
        $this->assertNull($data[0]->data);
        $this->assertEquals(0, $data[0]->fixed);
        $this->assertEquals(10, $data[0]->priority);
        $this->assertEquals('Admins', $data[0]->userGroupName);

        // Search for profile's name
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Usuarios');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(5, $data[0]->id);
        $this->assertEquals('permission', $data[0]->type);
        $this->assertNull($data[0]->userId);
        $this->assertNull($data[0]->userGroupId);
        $this->assertEquals(3, $data[0]->userProfileId);
        $this->assertNull($data[0]->data);
        $this->assertEquals(0, $data[0]->fixed);
        $this->assertEquals(10, $data[0]->priority);
        $this->assertEquals('Usuarios', $data[0]->userProfileName);

        // Search for no results
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('test_permission');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForCurrentUser()
    {
        $data = self::$service->getForCurrentUser('permission');

        $this->assertInstanceOf(ItemPresetData::class, $data);
        $this->assertEquals(2, $data->getId());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws NoSuchPropertyException
     */
    public function testCreate()
    {
        $accountPermission = new AccountPermission();
        $accountPermission->setUsersEdit([1, 2]);
        $accountPermission->setUsersView([3]);
        $accountPermission->setUserGroupsView([2]);
        $accountPermission->setUserGroupsEdit([1, 3]);

        $data = new ItemPresetData();
        $data->id = 6;
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 20;
        $data->type = 'permission';

        $request = new ItemPresetRequest($data, $accountPermission);

        $id = self::$service->create($request);

        $result = self::$service->getById($id);

        $this->assertEquals($data->id, $id);
        $this->assertEquals($data, $result);
        $this->assertEquals($accountPermission, $result->hydrate(AccountPermission::class));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreateDuplicatedHash()
    {
        $accountPermission = new AccountPermission();
        $accountPermission->setUsersEdit([1, 2]);
        $accountPermission->setUsersView([3]);
        $accountPermission->setUserGroupsView([2]);
        $accountPermission->setUserGroupsEdit([1, 3]);

        $data = new ItemPresetData();
        $data->userGroupId = 1;
        $data->fixed = 1;
        $data->priority = 10;
        $data->type = 'permission';

        $request = new ItemPresetRequest($data, $accountPermission);

        $this->expectException(ConstraintException::class);

        self::$service->create($request);
    }
}
