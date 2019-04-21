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

namespace SP\Tests\SP\Repositories;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\UserToUserGroupData;
use SP\Repositories\UserGroup\UserToUserGroupRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class UserToUserGroupRepositoryTest
 *
 * @package SP\Tests\SP\Repositories
 */
class UserToUserGroupRepositoryTest extends DatabaseTestCase
{
    /**
     * @var UserToUserGroupRepository
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

        self::$dataset = 'syspass_userGroup.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(UserToUserGroupRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetGroupsForUser()
    {
        $result = self::$repository->getGroupsForUser(3);

        $this->assertEquals(1, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals(2, $data[0]->userGroupId);

        $result = self::$repository->getGroupsForUser(2);

        $this->assertEquals(2, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]->userGroupId);
        $this->assertEquals(3, $data[1]->userGroupId);

        $this->assertEquals(0, self::$repository->getGroupsForUser(10)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $data = [3, 4];

        self::$repository->update(1, $data);

        $result = self::$repository->getById(1);

        $this->assertEquals(2, $result->getNumRows());

        /** @var UserToUserGroupData[] $data */
        $data = $result->getDataAsArray();

        $this->assertInstanceOf(UserToUserGroupData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getUserGroupId());
        $this->assertEquals(3, $data[0]->getUserId());

        $this->assertInstanceOf(UserToUserGroupData::class, $data[1]);
        $this->assertEquals(1, $data[1]->getUserGroupId());
        $this->assertEquals(4, $data[1]->getUserId());

        $this->expectException(ConstraintException::class);

        self::$repository->update(10, [3, 4]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(2);

        $this->assertEquals(2, $result->getNumRows());

        /** @var UserToUserGroupData[] $data */
        $data = $result->getDataAsArray();

        $this->assertCount(2, $data);

        $this->assertInstanceOf(UserToUserGroupData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getUserGroupId());
        $this->assertEquals(1, $data[0]->getUserId());

        $this->assertInstanceOf(UserToUserGroupData::class, $data[1]);
        $this->assertEquals(2, $data[1]->getUserGroupId());
        $this->assertEquals(3, $data[1]->getUserId());

        $data = self::$repository->getById(3)->getDataAsArray();

        $this->assertCount(1, $data);

        $this->assertInstanceOf(UserToUserGroupData::class, $data[0]);
        $this->assertEquals(3, $data[0]->getUserGroupId());
        $this->assertEquals(2, $data[0]->getUserId());

        $this->assertEquals(0, self::$repository->getById(10)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(1));

        $this->assertEquals(2, self::$repository->delete(2));

        $this->assertEquals(0, self::$repository->delete(10));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $data = [3, 4];

        self::$repository->add(1, $data);

        $result = self::$repository->getById(1);

        $this->assertEquals(3, $result->getNumRows());

        /** @var UserToUserGroupData[] $data */
        $data = $result->getDataAsArray();

        $this->assertCount(3, $data);

        $this->assertInstanceOf(UserToUserGroupData::class, $data[0]);

        $this->assertEquals(1, $data[0]->getUserGroupId());
        $this->assertEquals(2, $data[0]->getUserId());

        $this->assertInstanceOf(UserToUserGroupData::class, $data[1]);
        $this->assertEquals(1, $data[1]->getUserGroupId());
        $this->assertEquals(3, $data[1]->getUserId());

        $this->assertInstanceOf(UserToUserGroupData::class, $data[1]);
        $this->assertEquals(1, $data[2]->getUserGroupId());
        $this->assertEquals(4, $data[2]->getUserId());

        $this->expectException(ConstraintException::class);

        self::$repository->add(10, [3, 4]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAddDuplicated()
    {
        $data = [2, 3, 4];

        $this->expectException(ConstraintException::class);

        self::$repository->add(1, $data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckUserInGroup()
    {
        $this->assertTrue(self::$repository->checkUserInGroup(1, 2));

        $this->assertTrue(self::$repository->checkUserInGroup(2, 3));

        $this->assertFalse(self::$repository->checkUserInGroup(3, 3));
    }
}
