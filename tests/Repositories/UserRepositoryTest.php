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
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\User\UserRepository;
use SP\Services\User\UpdatePassRequest;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class UserRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a los usuarios
 *
 * @package SP\Tests
 */
class UserRepositoryTest extends DatabaseTestCase
{

    /**
     * @var UserRepository
     */
    private static $userRepository;

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
        self::$userRepository = $dic->get(UserRepository::class);
    }

    /**
     * Comprobar la actualización de usuarios
     *
     * @covers \SP\Repositories\User\UserRepository::checkDuplicatedOnUpdate()
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdate()
    {
        $userData = new UserData();
        $userData->setId(2);
        $userData->setName('Usuario Demo');
        $userData->setLogin('demo');
        $userData->setEmail('demo@syspass.org');
        $userData->setNotes('Usuario Demo');
        $userData->setUserGroupId(1);
        $userData->setUserProfileId(1);
        $userData->setIsAdminApp(1);
        $userData->setIsAdminAcc(1);
        $userData->setIsDisabled(1);
        $userData->setIsChangePass(1);
        $userData->setIsLdap(0);

        $this->assertEquals(1, self::$userRepository->update($userData));

        $userData->setId(3);

        $this->expectException(DuplicatedItemException::class);

        self::$userRepository->update($userData);

        $userData->setId(10);

        $this->assertEquals(0, self::$userRepository->update($userData));
    }

    /**
     * Comprobar la modificación de las preferencias de usuario
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testUpdatePreferencesById()
    {
        $preferences = new UserPreferencesData();
        $preferences->setLang('es_ED');
        $preferences->setAccountLink(true);
        $preferences->setOptionalActions(true);
        $preferences->setResultsAsCards(true);
        $preferences->setResultsPerPage(10);

        $this->assertEquals(1, self::$userRepository->updatePreferencesById(2, $preferences));
    }

    /**
     * Comprobar la obtención de los datos de un usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetById()
    {
        $user = self::$userRepository->getById(2);

        $this->assertInstanceOf(UserData::class, $user);
        $this->assertEquals('sysPass demo', $user->getName());
        $this->assertEquals('demo', $user->getLogin());

        $this->expectException(NoSuchItemException::class);

        self::$userRepository->getById(5);
    }

    /**
     * Comprobar si existe un usuario
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testCheckExistsByLogin()
    {
        $this->assertTrue(self::$userRepository->checkExistsByLogin('demo'));
        $this->assertFalse(self::$userRepository->checkExistsByLogin('usuario'));
    }

    /**
     * Comprobar los datos de uso de un usuario
     */
    public function testGetUsageForUser()
    {
        $this->assertCount(2, self::$userRepository->getUsageForUser(2));
    }

    /**
     * Comprobar la actualización de la clave de un usuario por Id
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdatePassById()
    {
        $result = self::$userRepository->updatePassById(2, new UpdatePassRequest(Hash::hashKey('prueba123')));

        $this->assertEquals(1, $result);

        $result = self::$userRepository->updatePassById(10, new UpdatePassRequest(Hash::hashKey('prueba123')));
        $this->assertEquals(0, $result);
    }

    /**
     * Obtener los datos de los usuarios por Id en lote
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $users = self::$userRepository->getByIdBatch([1, 2, 5]);

        $this->assertCount(2, $users);
        $this->assertInstanceOf(UserData::class, $users[0]);
        $this->assertEquals('admin', $users[0]->getLogin());
        $this->assertInstanceOf(UserData::class, $users[1]);
    }

    /**
     * Obtener los datos de todos los usuarios
     */
    public function testGetAll()
    {
        $users = self::$userRepository->getAll();

        $this->assertCount(4, $users);
        $this->assertInstanceOf(UserData::class, $users[0]);
        $this->assertEquals('admin', $users[0]->getLogin());
    }

    /**
     * Actualizar un usuario desde el proceso de login
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testUpdateOnLogin()
    {
        $userData = new UserData();
        $userData->setPass(Hash::hashKey('prueba123'));
        $userData->setName('prueba');
        $userData->setEmail('prueba@syspass.org');
        $userData->setIsLdap(1);
        $userData->setLogin('demo');

        $this->assertEquals(1, self::$userRepository->updateOnLogin($userData));

        $userData->setLogin('demodedadae');

        $this->assertEquals(0, self::$userRepository->updateOnLogin($userData));
    }

    /**
     * Eliminar usuarios en lote
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDeleteByIdBatch()
    {
        $this->expectException(ConstraintException::class);

        $result = self::$userRepository->deleteByIdBatch([1, 2, 5]);

        $this->assertCount(2, $result);
    }

    /**
     * Comprobar la obtención de los datos de un usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testGetByLogin()
    {
        $user = self::$userRepository->getByLogin('demo');

        $this->assertInstanceOf(UserData::class, $user);
        $this->assertEquals('sysPass demo', $user->getName());
        $this->assertEquals('demo', $user->getLogin());

        $this->expectException(NoSuchItemException::class);

        self::$userRepository->getByLogin('prueba');
    }

    /**
     * Comprobar la eliminación de usuarios
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDelete()
    {
        $result = self::$userRepository->delete(3);

        $this->assertEquals(1, $result);
        $this->assertEquals(3, $this->conn->getRowCount('User'));

        $this->expectException(ConstraintException::class);

        self::$userRepository->delete(1);
    }

    /**
     * Comprobar la obtención de los datos de usuarios
     */
    public function testGetBasicInfo()
    {
        $users = self::$userRepository->getBasicInfo();

        $this->assertCount(4, $users);
        $this->assertInstanceOf(UserData::class, $users[0]);
    }

    /**
     * Comprobar la modificación de los datos del último login
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateLastLoginById()
    {
        $this->assertEquals(1, self::$userRepository->updateLastLoginById(2));

        $this->assertEquals(0, self::$userRepository->updateLastLoginById(10));
    }

    /**
     * Comprobar la búsqueda de usuarios mediante texto
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('User A');

        $result = self::$userRepository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(\stdClass::class, $data[0]);
        $this->assertEquals(3, $data[0]->id);
        $this->assertEquals('User A', $data[0]->name);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$userRepository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdateMasterPassById()
    {
        $key = Crypt::makeSecuredKey('prueba123');
        $pass = Crypt::encrypt('prueba_key', $key, 'prueba123');

        $this->assertEquals(1, self::$userRepository->updateMasterPassById(3, $pass, $key));

        $user = self::$userRepository->getById(3);

        $this->assertEquals($pass, $user->getMPass());
        $this->assertEquals($key, $user->getMKey());
    }

    /**
     * Comprobar la creación de usuarios
     *
     * @covers \SP\Repositories\User\UserRepository::checkDuplicatedOnAdd()
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreate()
    {
        $userData = new UserData();
        $userData->setName('Usuario Prueba');
        $userData->setLogin('prueba');
        $userData->setEmail('prueba@syspass.org');
        $userData->setNotes('Usuario Prueba');
        $userData->setUserGroupId(1);
        $userData->setUserProfileId(1);
        $userData->setIsAdminApp(1);
        $userData->setIsAdminAcc(1);
        $userData->setIsDisabled(1);
        $userData->setIsChangePass(1);
        $userData->setIsLdap(0);
        $userData->setPass(Hash::hashKey('prueba123'));

        $this->assertEquals(5, self::$userRepository->create($userData));

        $userData->setLogin('demo');
        $userData->setEmail('prueba@syspass.org');

        $this->expectException(DuplicatedItemException::class);

        self::$userRepository->create($userData);

        $userData->setLogin('prueba');
        $userData->setEmail('demo@syspass.org');

        self::$userRepository->create($userData);
    }

    /**
     * Comprobar la obtención de email de usuario por Id de grupo
     */
    public function testGetUserEmailForGroup()
    {
        $this->assertCount(4, self::$userRepository->getUserEmailForGroup(2));
    }
}
