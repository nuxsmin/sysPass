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
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\UserProfile\UserProfileRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class UserProfileRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a los perfiles de usuarios
 *
 * @package SP\Tests
 */
class UserProfileRepositoryTest extends DatabaseTestCase
{
    /**
     * @var UserProfileRepository
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
        self::$repository = $dic->get(UserProfileRepository::class);
    }

    /**
     * Comprobar la obtención de perfiles
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();

        $this->assertEquals(3, $result->getNumRows());

        /** @var UserProfileData[] $data */
        $data = $result->getDataAsArray();

        $this->assertCount(3, $data);

        $this->assertInstanceOf(UserProfileData::class, $data[0]);
        $this->assertEquals('Admin', $data[0]->getName());

        $this->assertInstanceOf(UserProfileData::class, $data[1]);
        $this->assertEquals('Demo', $data[1]->getName());
    }

    /**
     * Comprobar la búsqueda de perfiles
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
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Demo', $data[0]->name);

        // Nueva búsqueda de perfil no existente
        $itemSearchData->setSeachString('prueba');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * Comprobar la actualización de perfiles
     *
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $data = new UserProfileData();
        $data->setId(2);
        $data->setName('Test Profile Demo');

        $this->assertEquals(1, self::$repository->update($data));

        $this->expectException(DuplicatedItemException::class);

        $data->setName('Admin');

        self::$repository->update($data);
    }

    /**
     * Comprobar la eliminación de perfiles
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $result = self::$repository->delete(3);

        $this->assertEquals(1, $result);
        $this->assertEquals(2, $this->conn->getRowCount('UserProfile'));

        $this->expectException(ConstraintException::class);

        self::$repository->delete(1);
    }

    /**
     * Comprobar si el perfil está en uso
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckInUse()
    {
        $this->assertTrue(self::$repository->checkInUse(1));
        $this->assertTrue(self::$repository->checkInUse(2));
        $this->assertFalse(self::$repository->checkInUse(3));
    }

    /**
     * Comprobar la creación de perfiles
     *
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
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

        $result = self::$repository->create($data);

        $this->assertEquals($data->getId(), $result);
        $this->assertEquals(4, $this->conn->getRowCount('UserProfile'));

        /** @var UserProfileData $resultData */
        $resultData = self::$repository->getById($result)->getData();

        $this->assertEquals($data->getId(), $resultData->getId());
        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals(serialize($data->getProfile()), $resultData->getProfile());
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

        self::$repository->create($data);
    }

    /**
     * Comprobar la obtención de perfiles por Id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(2);

        $this->assertEquals(1, $result->getNumRows());

        /** @var UserProfileData $data */
        $data = $result->getData();

        $this->assertInstanceOf(UserProfileData::class, $data);
        $this->assertEquals('Demo', $data->getName());
        $this->assertNotEmpty($data->getProfile());

        $this->assertEquals(0, self::$repository->getById(4)->getNumRows());
    }

    /**
     * Comprobar la obtención de los usuarios asociados a un perfil
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsersForProfile()
    {
        $this->assertEquals(1, self::$repository->getUsersForProfile(2)->getNumRows());

        $this->assertEquals(0, self::$repository->getUsersForProfile(3)->getNumRows());
    }

    /**
     * Comprobar la obtención de perfiles en lote
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $profiles = self::$repository->getByIdBatch([1, 2, 5]);

        $this->assertCount(2, $profiles);
        $this->assertInstanceOf(UserProfileData::class, $profiles[0]);
        $this->assertEquals(1, $profiles[0]->getId());
        $this->assertEquals('Admin', $profiles[0]->getName());
        $this->assertInstanceOf(UserProfileData::class, $profiles[1]);
    }

    /**
     * Comprobar la eliminación de perfiles en lote
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        // Se lanza excepción en caso de restricción relacional
        $this->expectException(ConstraintException::class);

        $result = self::$repository->deleteByIdBatch([1, 2, 3, 4]);

        $this->assertEquals(1, $result);
    }
}
