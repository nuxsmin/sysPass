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

namespace SP\Tests;

use DI\DependencyException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\UserGroup\UserGroupRepository;
use SP\Storage\DatabaseConnectionData;

/**
 * Class UserGroupRepositoryTest
 *
 * Tests unitarios para comprobar las consultas a la BBDD relativas a los grupos de usuarios
 *
 * @package SP\Tests
 */
class UserGroupRepositoryTestCase extends DatabaseTestCase
{
    /**
     * @var UserGroupRepository
     */
    private static $userGroupRepository;

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$userGroupRepository = $dic->get(UserGroupRepository::class);
    }

    /**
     * Comprobar la obtención de uso del grupo por usuarios
     */
    public function testGetUsageByUsers()
    {
        $this->assertCount(2, self::$userGroupRepository->getUsageByUsers(1));
        $this->assertCount(5, self::$userGroupRepository->getUsageByUsers(2));
        $this->assertCount(0, self::$userGroupRepository->getUsageByUsers(3));
    }

    /**
     * Comprobar si el grupo está en uso
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws QueryException
     */
    public function testCheckInUse()
    {
        $this->assertTrue(self::$userGroupRepository->checkInUse(1));
        $this->assertTrue(self::$userGroupRepository->checkInUse(2));
        $this->assertFalse(self::$userGroupRepository->checkInUse(5));
    }

    /**
     * Comprobar la obtención de grupos por nombre
     */
    public function testGetByName()
    {
        $group = self::$userGroupRepository->getByName('Demo');

        $this->assertInstanceOf(UserGroupData::class, $group);
        $this->assertEquals('Demo', $group->getName());
        $this->assertEmpty($group->getDescription());

        $group = self::$userGroupRepository->getByName('Prueba');
        $this->assertCount(0, $group);
    }

    /**
     * Comprobar la eliminación de grupos en lote
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        // Se lanza excepción en caso de restricción relacional
        $this->expectException(ConstraintException::class);

        $result = self::$userGroupRepository->deleteByIdBatch([1, 2, 3]);

        $this->assertEquals(1, $result);
    }

    /**
     * Comprobar la actualización de grupos
     *
     * @covers \SP\Repositories\UserGroup\UserGroupRepository::checkDuplicatedOnUpdate()
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $userGroupData = new UserGroupData();
        $userGroupData->setId(2);
        $userGroupData->setName('Grupo demo');
        $userGroupData->setDescription('Grupo para usuarios demo');

        $this->assertEquals(1, self::$userGroupRepository->update($userGroupData));

        $this->expectException(DuplicatedItemException::class);

        $userGroupData->setName('Admins');

        self::$userGroupRepository->update($userGroupData);

    }

    /**
     * Comprobar la obtención de grupos por Id
     */
    public function testGetById()
    {
        $group = self::$userGroupRepository->getById(2);

        $this->assertInstanceOf(UserGroupData::class, $group);
        $this->assertEquals('Demo', $group->getName());
        $this->assertEmpty($group->getDescription());

        $group = self::$userGroupRepository->getById(4);
        $this->assertCount(0, $group);
    }

    /**
     * Comprobar la creación de grupos
     *
     * @covers \SP\Repositories\UserGroup\UserGroupRepository::checkDuplicatedOnAdd()
     * @throws ConstraintException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreate()
    {
        $userGroupData = new UserGroupData();
        $userGroupData->setName('Grupo Prueba');
        $userGroupData->setDescription('Grupo de prueba para usuarios');

        $this->assertEquals(4, self::$userGroupRepository->create($userGroupData));

        $this->expectException(DuplicatedItemException::class);

        $userGroupData->setName('Admins');

        self::$userGroupRepository->create($userGroupData);
    }

    /**
     * Comprobar la obtención de grupos
     */
    public function testGetAll()
    {
        $groups = self::$userGroupRepository->getAll();

        $this->assertCount(3, $groups);
        $this->assertInstanceOf(UserGroupData::class, $groups[0]);
        $this->assertEquals('Admins', $groups[0]->getName());
        $this->assertInstanceOf(UserGroupData::class, $groups[1]);
        $this->assertEquals('Demo', $groups[1]->getName());
    }

    /**
     * Comprobar la eliminación de grupos
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testDelete()
    {
        $result = self::$userGroupRepository->delete(3);

        $this->assertEquals(1, $result);
        $this->assertEquals(2, $this->conn->getRowCount('UserGroup'));

        $this->expectException(ConstraintException::class);

        self::$userGroupRepository->delete(1);
        self::$userGroupRepository->delete(2);
    }

    /**
     * Comprobar la obtención de uso de grupos
     */
    public function testGetUsage()
    {
        $this->assertCount(7, self::$userGroupRepository->getUsage(2));
        $this->assertCount(0, self::$userGroupRepository->getUsage(3));
    }

    /**
     * Comprobar la obtención de grupos en lote
     */
    public function testGetByIdBatch()
    {
        $groups = self::$userGroupRepository->getByIdBatch([1, 2, 5]);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(UserGroupData::class, $groups[0]);
        $this->assertEquals(1, $groups[0]->getId());
        $this->assertEquals('Admins', $groups[0]->getName());
        $this->assertInstanceOf(UserGroupData::class, $groups[1]);
    }

    /**
     * Comprobar la búsqueda de grupos
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Demo');

        $search = self::$userGroupRepository->search($itemSearchData);
        $this->assertCount(2, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(1, $search['count']);
        $this->assertEquals(2, $search[0]->id);
        $this->assertEquals('Demo', $search[0]->name);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $search = self::$userGroupRepository->search($itemSearchData);
        $this->assertCount(1, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(0, $search['count']);
    }
}
