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

namespace SP\Tests\SP\Services\UserProfile;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\ServiceException;
use SP\Services\UserProfile\UserProfileService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class UserProfileServiceTest
 *
 * @package SP\Tests\SP\Services\UserProfile
 */
class UserProfileServiceTest extends DatabaseTestCase
{
    /**
     * @var UserProfileService
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

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(UserProfileService::class);
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
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Demo', $data[0]->name);

        // Nueva búsqueda de perfil no existente
        $itemSearchData->setSeachString('prueba');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $data = self::$service->getAllBasic();

        $this->assertCount(3, $data);

        $this->assertInstanceOf(UserProfileData::class, $data[0]);
        $this->assertEquals('Admin', $data[0]->getName());

        $this->assertInstanceOf(UserProfileData::class, $data[1]);
        $this->assertEquals('Demo', $data[1]->getName());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsersForProfile()
    {
        $this->assertCount(1, self::$service->getUsersForProfile(2));

        $this->assertCount(0, self::$service->getUsersForProfile(3));

        $this->assertCount(0, self::$service->getUsersForProfile(10));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $data = new UserProfileData();
        $data->setId(2);
        $data->setName('Test Profile');

        self::$service->update($data);

        $this->assertTrue(true);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdateUnknown()
    {
        $data = new UserProfileData();
        $data->setId(10);
        $data->setName('Test Profile');

        $this->expectException(ServiceException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdateDuplicated()
    {
        $data = new UserProfileData();
        $data->setId(2);
        $data->setName('Admin');

        $this->expectException(DuplicatedItemException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(1, self::$service->deleteByIdBatch([3]));

        $this->assertEquals(2, $this->conn->getRowCount('UserProfile'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatchUsed()
    {
        $this->expectException(ConstraintException::class);

        self::$service->deleteByIdBatch([1, 2]);

        $this->assertEquals(3, $this->conn->getRowCount('UserProfile'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatchUnknown()
    {
        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([3, 10]);

        $this->assertEquals(2, $this->conn->getRowCount('UserProfile'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetById()
    {
        $result = self::$service->getById(2);

        $this->assertInstanceOf(UserProfileData::class, $result);
        $this->assertInstanceOf(ProfileData::class, $result->getProfile());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        self::$service->delete(3);

        $this->assertEquals(2, $this->conn->getRowCount('UserProfile'));

        $this->expectException(ConstraintException::class);

        self::$service->delete(1);
    }

    /**
     * @throws SPException
     */
    public function testCreate()
    {
        $profileData = new ProfileData();
        $profileData->setAccAdd(true);
        $profileData->setAccDelete(true);
        $profileData->setConfigBackup(true);

        $data = new UserProfileData();
        $data->setId(4);
        $data->setName('Prueba');
        $data->setProfile($profileData);

        $result = self::$service->create($data);

        $this->assertEquals($data->getId(), $result);

        $this->assertEquals(4, $this->conn->getRowCount('UserProfile'));

        $this->assertEquals($data, self::$service->getById($result));
    }

    /**
     * @throws SPException
     */
    public function testCreateDuplicated()
    {
        $data = new UserProfileData();
        $data->setName('Admin');
        $data->setProfile(new ProfileData());

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }
}
