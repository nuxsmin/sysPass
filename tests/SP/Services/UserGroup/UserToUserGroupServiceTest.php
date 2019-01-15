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

use SP\Core\Exceptions\ConstraintException;
use SP\DataModel\UserToUserGroupData;
use SP\Repositories\NoSuchItemException;
use SP\Services\UserGroup\UserToUserGroupService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class UserToUserGroupServiceTest
 *
 * @package SP\Tests\SP\Services\UserGroup
 */
class UserToUserGroupServiceTest extends DatabaseTestCase
{

    /**
     * @var UserToUserGroupService
     */
    private static $service;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_userGroup.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(UserToUserGroupService::class);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCheckUserInGroup()
    {
        $this->assertTrue(self::$service->checkUserInGroup(1, 2));

        $this->assertTrue(self::$service->checkUserInGroup(2, 3));

        $this->assertFalse(self::$service->checkUserInGroup(3, 3));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetGroupsForUser()
    {
        $data = self::$service->getGroupsForUser(3);

        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->userGroupId);

        $data = self::$service->getGroupsForUser(2);

        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]->userGroupId);
        $this->assertEquals(3, $data[1]->userGroupId);

        $data = self::$service->getGroupsForUser(10);

        $this->assertCount(0, $data);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAdd()
    {
        $data = [3, 4];

        self::$service->add(1, $data);

        $this->assertEquals([2, 3, 4], self::$service->getUsersByGroupId(1));

        $this->expectException(ConstraintException::class);

        self::$service->add(10, $data);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAddDuplicated()
    {
        $data = [2, 3, 4];

        $this->expectException(ConstraintException::class);

        self::$service->add(1, $data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $data = [3, 4];

        self::$service->update(1, $data);

        $this->assertEquals($data, self::$service->getUsersByGroupId(1));

        $this->expectException(ConstraintException::class);

        self::$service->update(10, $data);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function testGetById()
    {
        $data = self::$service->getById(2);

        $this->assertCount(2, $data);

        $this->assertInstanceOf(UserToUserGroupData::class, $data[0]);

        $this->assertEquals(2, $data[0]->getUserGroupId());
        $this->assertEquals(1, $data[0]->getUserId());

        $this->assertEquals(2, $data[1]->getUserGroupId());
        $this->assertEquals(3, $data[1]->getUserId());

        $data = self::$service->getById(3);

        $this->assertCount(1, $data);

        $this->assertEquals(3, $data[0]->getUserGroupId());
        $this->assertEquals(2, $data[0]->getUserId());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetUsersByGroupId()
    {
        $data = self::$service->getUsersByGroupId(2);

        $this->assertCount(2, $data);

        $this->assertEquals([1, 3], $data);
    }
}
