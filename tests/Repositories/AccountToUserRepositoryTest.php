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
use SP\Account\AccountRequest;
use SP\Core\Exceptions\ConstraintException;
use SP\DataModel\ItemData;
use SP\Repositories\Account\AccountToUserRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountToUserRepositoryTest
 *
 * Tests de integración para la comprobación de operaciones de usuarios asociados a cuentas
 *
 * @package SP\Tests
 */
class AccountToUserRepositoryTest extends DatabaseTestCase
{
    /**
     * @var AccountToUserRepository
     */
    private static $accountToUserRepository;

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
        self::$accountToUserRepository = $dic->get(AccountToUserRepository::class);
    }

    /**
     * Comprobar la actualización de usuarios por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->usersView = [1, 2, 3];

        self::$accountToUserRepository->update($accountRequest);

        $users = self::$accountToUserRepository->getUsersByAccountId($accountRequest->id);

        $this->assertCount(3, $users);
        $this->assertInstanceOf(ItemData::class, $users[0]);
        $this->assertEquals(0, (int)$users[0]->isEdit);
        $this->assertInstanceOf(ItemData::class, $users[1]);
        $this->assertEquals(0, (int)$users[1]->isEdit);
        $this->assertInstanceOf(ItemData::class, $users[2]);
        $this->assertEquals(0, (int)$users[2]->isEdit);

        $this->expectException(ConstraintException::class);

        $accountRequest->usersView = [10];

        self::$accountToUserRepository->update($accountRequest);

        $accountRequest->id = 3;
        $accountRequest->usersView = [1, 2, 3];

        self::$accountToUserRepository->update($accountRequest);
    }

    /**
     * Comprobar la obtención de usuarios por Id de cuenta
     */
    public function testGetUsersByAccountId()
    {
        $users = self::$accountToUserRepository->getUsersByAccountId(1);

        $this->assertCount(1, $users);
        $this->assertInstanceOf(ItemData::class, $users[0]);

        $usersView = array_filter($users, function ($user) {
            return (int)$user->isEdit === 0;
        });

        $this->assertCount(0, $usersView);

        $usersEdit = array_filter($users, function ($user) {
            return (int)$user->isEdit === 1;
        });

        $this->assertCount(1, $usersEdit);

        $users = self::$accountToUserRepository->getUsersByAccountId(2);

        $this->assertCount(1, $users);
        $this->assertInstanceOf(ItemData::class, $users[0]);

        $usersView = array_filter($users, function ($user) {
            return (int)$user->isEdit === 0;
        });

        $this->assertCount(1, $usersView);

        $usersEdit = array_filter($users, function ($user) {
            return (int)$user->isEdit === 1;
        });

        $this->assertCount(0, $usersEdit);

        $users = self::$accountToUserRepository->getUsersByAccountId(3);

        $this->assertCount(0, $users);
    }

    /**
     * Comprobar la actualización de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateEdit()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->usersEdit = [2, 3];

        self::$accountToUserRepository->updateEdit($accountRequest);

        $users = self::$accountToUserRepository->getUsersByAccountId($accountRequest->id);

        $this->assertCount(2, $users);
        $this->assertInstanceOf(ItemData::class, $users[0]);
        $this->assertEquals(1, (int)$users[0]->isEdit);
        $this->assertInstanceOf(ItemData::class, $users[1]);
        $this->assertEquals(1, (int)$users[1]->isEdit);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->usersEdit = [10];

        self::$accountToUserRepository->updateEdit($accountRequest);

        // Comprobar que se lanza excepción al añadir usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->usersEdit = [2, 3];

        self::$accountToUserRepository->updateEdit($accountRequest);
    }

    /**
     * Comprobar la eliminación de usuarios por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByAccountId()
    {
        $this->assertEquals(1, self::$accountToUserRepository->deleteByAccountId(1));
        $this->assertCount(0, self::$accountToUserRepository->getUsersByAccountId(1));

        $this->assertEquals(0, self::$accountToUserRepository->deleteByAccountId(10));

        $this->assertEquals(1, $this->conn->getRowCount('AccountToUser'));
    }

    /**
     * Comprobar la insercción de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAddEdit()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->usersEdit = [1, 2, 3];

        self::$accountToUserRepository->addEdit($accountRequest);

        $users = self::$accountToUserRepository->getUsersByAccountId($accountRequest->id);

        $this->assertCount(3, $users);
        $this->assertInstanceOf(ItemData::class, $users[0]);
        $this->assertInstanceOf(ItemData::class, $users[1]);
        $this->assertInstanceOf(ItemData::class, $users[2]);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->usersEdit = [10];

        self::$accountToUserRepository->addEdit($accountRequest);

        // Comprobar que se lanza excepción al añadir usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->usersEdit = [1, 2, 3];

        self::$accountToUserRepository->addEdit($accountRequest);
    }

    /**
     * Comprobar la insercción de usuarios por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAdd()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->usersView = [1, 2, 3];

        self::$accountToUserRepository->add($accountRequest);

        $users = self::$accountToUserRepository->getUsersByAccountId($accountRequest->id);

        $this->assertCount(3, $users);
        $this->assertInstanceOf(ItemData::class, $users[0]);
        $this->assertInstanceOf(ItemData::class, $users[1]);
        $this->assertInstanceOf(ItemData::class, $users[2]);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->usersView = [10];

        self::$accountToUserRepository->add($accountRequest);

        // Comprobar que se lanza excepción al añadir usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->usersView = [1, 2, 3];

        self::$accountToUserRepository->add($accountRequest);
    }

    /**
     * Comprobar la eliminación de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteEditByAccountId()
    {
        $this->assertEquals(1, self::$accountToUserRepository->deleteEditByAccountId(1));
        $this->assertCount(0, self::$accountToUserRepository->getUsersByAccountId(1));

        $this->assertEquals(0, self::$accountToUserRepository->deleteEditByAccountId(10));

        $this->assertEquals(1, $this->conn->getRowCount('AccountToUser'));
    }
}
