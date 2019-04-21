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
use SP\DataModel\ItemData;
use SP\Repositories\Account\AccountToUserRepository;
use SP\Services\Account\AccountRequest;
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
        self::$repository = $dic->get(AccountToUserRepository::class);
    }

    /**
     * Comprobar la obtención de usuarios por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsersByAccountId()
    {
        $result = self::$repository->getUsersByAccountId(1);
        $this->assertEquals(1, $result->getNumRows());

        $resultData = $result->getDataAsArray();

        $this->assertCount(1, $resultData);
        $this->assertInstanceOf(ItemData::class, $resultData[0]);

        $usersView = array_filter($resultData, function ($user) {
            return (int)$user->isEdit === 0;
        });

        $this->assertCount(0, $usersView);

        $usersEdit = array_filter($resultData, function ($user) {
            return (int)$user->isEdit === 1;
        });

        $this->assertCount(1, $usersEdit);

        $result = self::$repository->getUsersByAccountId(2);
        $this->assertEquals(1, $result->getNumRows());

        $resultData = $result->getDataAsArray();

        $this->assertCount(1, $resultData);
        $this->assertInstanceOf(ItemData::class, $resultData[0]);

        $usersView = array_filter($resultData, function ($user) {
            return (int)$user->isEdit === 0;
        });

        $this->assertCount(1, $usersView);

        $usersEdit = array_filter($resultData, function ($user) {
            return (int)$user->isEdit === 1;
        });

        $this->assertCount(0, $usersEdit);

        $this->assertEquals(0, self::$repository->getUsersByAccountId(3)->getNumRows());
    }

    /**
     * Comprobar la actualización de usuarios por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->usersView = [1, 2, 3];

        self::$repository->updateByType($accountRequest, false);

        $result = self::$repository->getUsersByAccountId($accountRequest->id);
        $this->assertEquals(3, $result->getNumRows());

        $resultData = $result->getDataAsArray();

        $this->assertCount(3, $resultData);
        $this->assertInstanceOf(ItemData::class, $resultData[0]);
        $this->assertEquals(0, (int)$resultData[0]->isEdit);
        $this->assertInstanceOf(ItemData::class, $resultData[1]);
        $this->assertEquals(0, (int)$resultData[1]->isEdit);
        $this->assertInstanceOf(ItemData::class, $resultData[2]);
        $this->assertEquals(0, (int)$resultData[2]->isEdit);

        $this->expectException(ConstraintException::class);

        $accountRequest->usersView = [10];

        self::$repository->updateByType($accountRequest, false);

        $accountRequest->id = 3;
        $accountRequest->usersView = [1, 2, 3];

        self::$repository->updateByType($accountRequest, false);
    }

    /**
     * Comprobar la actualización de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateEdit()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->usersEdit = [2, 3];

        self::$repository->updateByType($accountRequest, true);

        $result = self::$repository->getUsersByAccountId($accountRequest->id);
        $this->assertEquals(2, $result->getNumRows());

        $resultData = $result->getDataAsArray();

        $this->assertCount(2, $resultData);
        $this->assertInstanceOf(ItemData::class, $resultData[0]);
        $this->assertEquals(1, (int)$resultData[0]->isEdit);
        $this->assertInstanceOf(ItemData::class, $resultData[1]);
        $this->assertEquals(1, (int)$resultData[1]->isEdit);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->usersEdit = [10];

        self::$repository->updateByType($accountRequest, true);

        // Comprobar que se lanza excepción al añadir usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->usersEdit = [2, 3];

        self::$repository->updateByType($accountRequest, true);
    }

    /**
     * Comprobar la eliminación de usuarios por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteViewByAccountId()
    {
        $this->assertEquals(1, self::$repository->deleteTypeByAccountId(2, false));
        $this->assertEquals(0, self::$repository->getUsersByAccountId(2)->getNumRows());

        $this->assertEquals(0, self::$repository->deleteTypeByAccountId(10, false));

        $this->assertEquals(1, $this->conn->getRowCount('AccountToUser'));
    }

    /**
     * Comprobar la eliminación de usuarios por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByAccountId()
    {
        $this->assertEquals(1, self::$repository->deleteByAccountId(1));
        $this->assertEquals(0, self::$repository->getUsersByAccountId(1)->getNumRows());

        $this->assertEquals(0, self::$repository->deleteByAccountId(10));

        $this->assertEquals(1, $this->conn->getRowCount('AccountToUser'));
    }

    /**
     * Comprobar la insercción de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAddEdit()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->usersEdit = [1, 2, 3];

        self::$repository->addByType($accountRequest, true);

        $result = self::$repository->getUsersByAccountId($accountRequest->id);
        $this->assertEquals(3, $result->getNumRows());

        $resultData = $result->getDataAsArray();

        $this->assertCount(3, $resultData);
        $this->assertInstanceOf(ItemData::class, $resultData[0]);
        $this->assertInstanceOf(ItemData::class, $resultData[1]);
        $this->assertInstanceOf(ItemData::class, $resultData[2]);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->usersEdit = [10];

        self::$repository->addByType($accountRequest, true);

        // Comprobar que se lanza excepción al añadir usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->usersEdit = [1, 2, 3];

        self::$repository->addByType($accountRequest, true);
    }

    /**
     * Comprobar la insercción de usuarios por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->usersView = [1, 2, 3];

        self::$repository->addByType($accountRequest, false);

        $result = self::$repository->getUsersByAccountId($accountRequest->id);
        $this->assertEquals(3, $result->getNumRows());

        $resultData = $result->getDataAsArray();

        $this->assertCount(3, $resultData);
        $this->assertInstanceOf(ItemData::class, $resultData[0]);
        $this->assertInstanceOf(ItemData::class, $resultData[1]);
        $this->assertInstanceOf(ItemData::class, $resultData[2]);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->usersView = [10];

        self::$repository->addByType($accountRequest, false);

        // Comprobar que se lanza excepción al añadir usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->usersView = [1, 2, 3];

        self::$repository->addByType($accountRequest, false);
    }

    /**
     * Comprobar la eliminación de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteEditByAccountId()
    {
        $this->assertEquals(1, self::$repository->deleteTypeByAccountId(1, true));
        $this->assertEquals(0, self::$repository->getUsersByAccountId(1)->getNumRows());

        $this->assertEquals(0, self::$repository->deleteTypeByAccountId(10, true));

        $this->assertEquals(1, $this->conn->getRowCount('AccountToUser'));
    }
}
