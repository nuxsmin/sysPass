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

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\User\UserRepository;
use SP\Services\User\UpdatePassRequest;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
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
        self::$repository = $dic->get(UserRepository::class);
    }

    /**
     * Comprobar la actualización de usuarios
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
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

        $this->assertEquals(1, self::$repository->update($userData));

        $userData->setId(3);

        $this->expectException(DuplicatedItemException::class);

        self::$repository->update($userData);

        $userData->setId(10);

        $this->assertEquals(0, self::$repository->update($userData));
    }

    /**
     * Comprobar la modificación de las preferencias de usuario
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testUpdatePreferencesById()
    {
        $preferences = new UserPreferencesData();
        $preferences->setLang('es_ES');
        $preferences->setAccountLink(true);
        $preferences->setOptionalActions(true);
        $preferences->setResultsAsCards(true);
        $preferences->setResultsPerPage(10);

        $this->assertEquals(1, self::$repository->updatePreferencesById(2, $preferences));
    }

    /**
     * Comprobar la obtención de los datos de un usuario
     *
     * @throws SPException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(2);

        $this->assertEquals(1, $result->getNumRows());

        /** @var UserData $data */
        $data = $result->getData();

        $this->assertInstanceOf(UserData::class, $data);
        $this->assertEquals('sysPass demo', $data->getName());
        $this->assertEquals('demo', $data->getLogin());

        $result = self::$repository->getById(5);

        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * Comprobar si existe un usuario
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testCheckExistsByLogin()
    {
        $this->assertTrue(self::$repository->checkExistsByLogin('demo'));
        $this->assertFalse(self::$repository->checkExistsByLogin('usuario'));
    }

    /**
     * Comprobar los datos de uso de un usuario
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageForUser()
    {
        $result = self::$repository->getUsageForUser(2);

        $this->assertEquals(2, $result->getNumRows());

        $this->assertCount(2, $result->getDataAsArray());
    }

    /**
     * Comprobar la actualización de la clave de un usuario por Id
     *
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testUpdatePassById()
    {
        $result = self::$repository->updatePassById(2, new UpdatePassRequest(Hash::hashKey('test123')));

        $this->assertEquals(1, $result);

        /** @var UserData $data */
        $data = self::$repository->getById(2)->getData();

        $this->assertTrue(Hash::checkHashKey('test123', $data->getPass()));

        $result = self::$repository->updatePassById(10, new UpdatePassRequest(Hash::hashKey('test123')));

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
        $users = self::$repository->getByIdBatch([1, 2, 5]);

        $this->assertCount(2, $users);
        $this->assertInstanceOf(UserData::class, $users[0]);
        $this->assertEquals('admin', $users[0]->getLogin());
        $this->assertInstanceOf(UserData::class, $users[1]);
    }

    /**
     * Obtener los datos de todos los usuarios
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $users = self::$repository->getAll();

        $this->assertCount(4, $users);
        $this->assertInstanceOf(UserData::class, $users[0]);
        $this->assertEquals('admin', $users[0]->getLogin());
    }

    /**
     * Actualizar un usuario desde el proceso de login
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testUpdateOnLogin()
    {
        $userData = new UserData();
        $userData->setPass(Hash::hashKey('prueba123'));
        $userData->setName('prueba');
        $userData->setEmail('prueba@syspass.org');
        $userData->setIsLdap(1);
        $userData->setLogin('demo');

        $this->assertEquals(1, self::$repository->updateOnLogin($userData));

        $userData->setLogin('demodedadae');

        $this->assertEquals(0, self::$repository->updateOnLogin($userData));
    }

    /**
     * Eliminar usuarios en lote
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDeleteByIdBatch()
    {
        $this->expectException(ConstraintException::class);

        $result = self::$repository->deleteByIdBatch([1, 2, 5]);

        $this->assertCount(2, $result);
    }

    /**
     * Comprobar la obtención de los datos de un usuario
     *
     * @throws SPException
     */
    public function testGetByLogin()
    {
        $result = self::$repository->getByLogin('demo');

        $this->assertEquals(1, $result->getNumRows());

        /** @var UserData $data */
        $data = $result->getData();

        $this->assertInstanceOf(UserData::class, $data);
        $this->assertEquals('sysPass demo', $data->getName());
        $this->assertEquals('demo', $data->getLogin());

        $this->assertEquals(0, self::$repository->getByLogin('prueba')->getNumRows());
    }

    /**
     * Comprobar la eliminación de usuarios
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDelete()
    {
        $result = self::$repository->delete(3);

        $this->assertEquals(1, $result);
        $this->assertEquals(3, $this->conn->getRowCount('User'));

        $this->expectException(ConstraintException::class);

        self::$repository->delete(1);
    }

    /**
     * Comprobar la obtención de los datos de usuarios
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetBasicInfo()
    {
        $result = self::$repository->getBasicInfo();

        $this->assertEquals(4, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(4, $data);
        $this->assertInstanceOf(UserData::class, $data[0]);
    }

    /**
     * Comprobar la modificación de los datos del último login
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateLastLoginById()
    {
        $this->assertEquals(1, self::$repository->updateLastLoginById(2));

        $this->assertEquals(0, self::$repository->updateLastLoginById(10));
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

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(3, $data[0]->id);
        $this->assertEquals('User A', $data[0]->name);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws CryptoException
     * @throws SPException
     */
    public function testUpdateMasterPassById()
    {
        $key = Crypt::makeSecuredKey('prueba123');
        $pass = Crypt::encrypt('prueba_key', $key, 'prueba123');

        $this->assertEquals(1, self::$repository->updateMasterPassById(3, $pass, $key));

        $result = self::$repository->getById(3);

        $this->assertEquals(1, $result->getNumRows());

        /** @var UserData $data */
        $data = $result->getData();

        $this->assertEquals($pass, $data->getMPass());
        $this->assertEquals($key, $data->getMKey());
    }

    /**
     * Comprobar la creación de usuarios
     *
     * @throws SPException
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

        $this->assertEquals(5, self::$repository->create($userData));

        $userData->setLogin('demo');
        $userData->setEmail('prueba@syspass.org');

        $this->expectException(DuplicatedItemException::class);

        self::$repository->create($userData);

        $userData->setLogin('prueba');
        $userData->setEmail('demo@syspass.org');

        self::$repository->create($userData);
    }

    /**
     * Comprobar la obtención de email de usuario por Id de grupo
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUserEmailForGroup()
    {
        $this->assertEquals(4, self::$repository->getUserEmailForGroup(2)->getNumRows());

        $this->assertEquals(0, self::$repository->getUserEmailForGroup(10)->getNumRows());
    }
}
