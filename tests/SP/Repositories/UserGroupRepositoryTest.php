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
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\UserGroup\UserGroupRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class UserGroupRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a los grupos de usuarios
 *
 * @package SP\Tests
 */
class UserGroupRepositoryTestCase extends DatabaseTestCase
{
    /**
     * @var UserGroupRepository
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
        self::$repository = $dic->get(UserGroupRepository::class);
    }

    /**
     * Comprobar la obtención de uso del grupo por usuarios
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageByUsers()
    {
        $this->assertEquals(2, self::$repository->getUsageByUsers(1)->getNumRows());

        $this->assertEquals(5, self::$repository->getUsageByUsers(2)->getNumRows());

        $this->assertEquals(0, self::$repository->getUsageByUsers(4)->getNumRows());
    }

    /**
     * Comprobar si el grupo está en uso
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckInUse()
    {
        $this->assertTrue(self::$repository->checkInUse(1));

        $this->assertTrue(self::$repository->checkInUse(2));

        $this->assertFalse(self::$repository->checkInUse(5));
    }

    /**
     * Comprobar la obtención de grupos por nombre
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $result = self::$repository->getByName('Demo');

        $this->assertEquals(1, $result->getNumRows());

        /** @var UserGroupData $data */
        $data = $result->getData();

        $this->assertInstanceOf(UserGroupData::class, $data);
        $this->assertEquals('Demo', $data->getName());
        $this->assertEmpty($data->getDescription());

        $this->assertEquals(0, self::$repository->getByName('Prueba')->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$repository->deleteByIdBatch([4, 5]));

        $this->assertEquals(3, $this->conn->getRowCount('UserGroup'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchUsed()
    {
        // Se lanza excepción en caso de restricción relacional
        $this->expectException(ConstraintException::class);

        self::$repository->deleteByIdBatch([1, 2]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchUnknown()
    {
        $this->assertEquals(2, self::$repository->deleteByIdBatch([4, 5, 10]));

        $this->assertEquals(3, $this->conn->getRowCount('UserGroup'));
    }

    /**
     * Comprobar la actualización de grupos
     *
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $data = new UserGroupData();
        $data->setId(2);
        $data->setName('Grupo demo');
        $data->setDescription('Grupo para usuarios demo');

        $this->assertEquals(1, self::$repository->update($data));

        $this->expectException(DuplicatedItemException::class);

        $data->setName('Admins');

        self::$repository->update($data);

    }

    /**
     * Comprobar la obtención de grupos por Id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(2);

        $this->assertEquals(1, $result->getNumRows());

        /** @var UserGroupData $data */
        $data = $result->getData();

        $this->assertInstanceOf(UserGroupData::class, $data);
        $this->assertEquals('Demo', $data->getName());
        $this->assertEmpty($data->getDescription());

        $this->assertEquals(0, self::$repository->getById(10)->getNumRows());
    }

    /**
     * Comprobar la creación de grupos
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        $data = new UserGroupData();
        $data->setId(6);
        $data->setName('Grupo Prueba');
        $data->setDescription('Grupo de prueba para usuarios');

        $this->assertEquals($data->getId(), self::$repository->create($data));

        $this->assertEquals($data, self::$repository->getById($data->getId())->getData());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreateDuplicated()
    {
        $data = new UserGroupData();
        $data->setName('Admins');
        $data->setDescription('Group for demo users');

        $this->expectException(DuplicatedItemException::class);

        self::$repository->create($data);
    }

    /**
     * Comprobar la obtención de grupos
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();

        $this->assertEquals(5, $result->getNumRows());

        /** @var UserGroupData[] $data */
        $data = $result->getDataAsArray();

        $this->assertCount(5, $data);

        $this->assertInstanceOf(UserGroupData::class, $data[0]);
        $this->assertEquals('Admins', $data[0]->getName());
        $this->assertEquals('sysPass Admins', $data[0]->getDescription());
        $this->assertInstanceOf(UserGroupData::class, $data[1]);

        $this->assertEquals('Demo', $data[1]->getName());
        $this->assertEmpty($data[1]->getDescription());
    }

    /**
     * Comprobar la eliminación de grupos
     *
     * @throws SPException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(3));

        $this->assertEquals(4, $this->conn->getRowCount('UserGroup'));

        $this->assertEquals(0, self::$repository->delete(10));
    }

    /**
     * Comprobar la eliminación de grupos
     *
     * @throws SPException
     */
    public function testDeleteUsed()
    {
        $this->expectException(ConstraintException::class);

        self::$repository->delete(1);
    }

    /**
     * Comprobar la obtención de uso de grupos
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsage()
    {
        $this->assertEquals(7, self::$repository->getUsage(2)->getNumRows());

        $this->assertEquals(1, self::$repository->getUsage(3)->getNumRows());

        $this->assertEquals(0, self::$repository->getUsage(4)->getNumRows());
    }

    /**
     * Comprobar la obtención de grupos en lote
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $data = self::$repository->getByIdBatch([1, 2, 10]);

        $this->assertCount(2, $data);
        $this->assertInstanceOf(UserGroupData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals('Admins', $data[0]->getName());
        $this->assertEquals('sysPass Admins', $data[0]->getDescription());

        $this->assertInstanceOf(UserGroupData::class, $data[1]);
        $this->assertEquals('Demo', $data[1]->getName());
        $this->assertEmpty($data[1]->getDescription());
    }

    /**
     * Comprobar la búsqueda de grupos
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Demo');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(UserGroupData::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Demo', $data[0]->name);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }
}
