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
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\User\UserRepository;
use SP\Services\User\UpdatePassRequest;
use SP\Storage\DatabaseConnectionData;

/**
 * Class UserRepositoryTest
 *
 * @package SP\Tests
 */
class UserRepositoryTest extends DatabaseBaseTest
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

        $userData = new UserData();
        $userData->setId(10);

        $this->expectException(QueryException::class);

        self::$userRepository->update($userData);
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

        $this->assertTrue(self::$userRepository->updatePreferencesById(2, $preferences));
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
        $this->assertEquals('sysPass Demo', $user->getName());
        $this->assertEquals('demo', $user->getLogin());

        $this->expectException(NoSuchItemException::class);

        self::$userRepository->getById(3);
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
        $this->assertCount(1, self::$userRepository->getUsageForUser(2));
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

        $this->assertTrue($result);
    }

    /**
     * Obtener los datos de los usuarios por Id en lote
     */
    public function testGetByIdBatch()
    {
        $users = self::$userRepository->getByIdBatch([1, 2, 5]);

        $this->assertCount(2, $users);
        $this->assertInstanceOf(UserData::class, $users[0]);
        $this->assertEquals('admin', $users[0]->getName());
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
        $this->assertEquals('admin', $users[0]->getName());
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

        $result = self::$userRepository->updateOnLogin($userData);

        $this->assertTrue($result);
    }

    /**
     * Eliminar usuarios en lote
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDeleteByIdBatch()
    {
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
        $this->assertEquals('sysPass Demo', $user->getName());
        $this->assertEquals('demo', $user->getLogin());

        $this->expectException(NoSuchItemException::class);

        self::$userRepository->getByLogin('prueba');
    }

    /**
     * Comprobar la eliminación de un usuario
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDelete()
    {
        $result = self::$userRepository->delete(2);

        $this->assertEquals(1, $result);

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
        $result = self::$userRepository->updateLastLoginById(2);

        $this->assertTrue($result);
    }

    public function testSearch()
    {

    }

    public function testUpdateMasterPassById()
    {

    }

    public function testCreate()
    {

    }

    public function testGetUserEmailForGroup()
    {

    }
}
