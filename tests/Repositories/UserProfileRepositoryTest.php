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
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\UserProfile\UserProfileRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
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
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

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
        $profiles = self::$repository->getAll();

        $this->assertCount(3, $profiles);
        $this->assertInstanceOf(UserProfileData::class, $profiles[0]);
        $this->assertEquals('Admin', $profiles[0]->getName());
        $this->assertInstanceOf(UserProfileData::class, $profiles[1]);
        $this->assertEquals('Demo', $profiles[1]->getName());
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
        $this->assertInstanceOf(\stdClass::class, $data[0]);
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
     * @covers \SP\Repositories\UserGroup\UserGroupRepository::checkDuplicatedOnUpdate()
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $userProfileData = new UserProfileData();
        $userProfileData->setId(2);
        $userProfileData->setName('Perfil Demo');

        $this->assertEquals(1, self::$repository->update($userProfileData));

        $this->expectException(DuplicatedItemException::class);

        $userProfileData->setName('Admin');

        self::$repository->update($userProfileData);
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
        self::$repository->delete(2);
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

        $userProfileData = new UserProfileData();
        $userProfileData->setName('Prueba');
        $userProfileData->setProfile($profileData);

        $result = self::$repository->create($userProfileData);

        $this->assertEquals(4, $result);
        $this->assertEquals(4, $this->conn->getRowCount('UserProfile'));

        $this->expectException(DuplicatedItemException::class);

        $userProfileData->setName('Demo');

        self::$repository->create($userProfileData);
    }

    /**
     * Comprobar la obtención de perfiles por Id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $profile = self::$repository->getById(2);

        $this->assertInstanceOf(UserProfileData::class, $profile);
        $this->assertEquals('Demo', $profile->getName());
        $this->assertNotEmpty($profile->getProfile());

        $this->assertNull(self::$repository->getById(4));
    }

    /**
     * Comprobar la obtención de los usuarios asociados a un perfil
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsersForProfile()
    {
        $this->assertCount(1, self::$repository->getUsersForProfile(2));
        $this->assertCount(0, self::$repository->getUsersForProfile(3));
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
