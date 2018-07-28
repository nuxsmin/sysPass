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

namespace SP\Test\Repositories;

use DI\DependencyException;
use SP\Core\Exceptions\ConstraintException;
use SP\DataModel\ItemData;
use SP\Repositories\Account\AccountToUserGroupRepository;
use SP\Services\Account\AccountRequest;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Test\DatabaseTestCase;
use function SP\Test\setupContext;

/**
 * Class AccountToUserGroupRepositoryTest
 *
 * Tests de integración para la comprobación de operaciones de grupos de usuarios asociados a cuentas
 *
 * @package SP\Tests
 */
class AccountToUserGroupRepositoryTest extends DatabaseTestCase
{
    /**
     * @var AccountToUserGroupRepository
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

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(AccountToUserGroupRepository::class);
    }

    /**
     * Comprobar la obtención de grupos de usuarios por Id de cuenta
     *
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetUserGroupsByAccountId()
    {
        $result = self::$repository->getUserGroupsByAccountId(1);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(ItemData::class, $data[0]);

        $userGroupsView = array_filter($data, function ($user) {
            return (int)$user->isEdit === 0;
        });

        $this->assertCount(0, $userGroupsView);

        $userGroupsEdit = array_filter($data, function ($user) {
            return (int)$user->isEdit === 1;
        });

        $this->assertCount(1, $userGroupsEdit);

        $result = self::$repository->getUserGroupsByAccountId(2);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(ItemData::class, $data[0]);

        $userGroupsView = array_filter($data, function ($user) {
            return (int)$user->isEdit === 0;
        });

        $this->assertCount(1, $userGroupsView);

        $userGroupsEdit = array_filter($data, function ($user) {
            return (int)$user->isEdit === 1;
        });

        $this->assertCount(0, $userGroupsEdit);

        $this->assertEquals(0, self::$repository->getUserGroupsByAccountId(3)->getNumRows());
    }

    /**
     * Comprobar la actualización de grupos de usuarios por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->userGroupsView = [1, 2, 3];

        $this->assertEquals(3, self::$repository->update($accountRequest));

        $result = self::$repository->getUserGroupsByAccountId($accountRequest->id);
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertInstanceOf(ItemData::class, $data[0]);
        $this->assertEquals(0, (int)$data[0]->isEdit);
        $this->assertInstanceOf(ItemData::class, $data[1]);
        $this->assertEquals(0, (int)$data[1]->isEdit);
        $this->assertInstanceOf(ItemData::class, $data[2]);
        $this->assertEquals(0, (int)$data[2]->isEdit);

        $this->expectException(ConstraintException::class);

        $accountRequest->userGroupsView = [10];

        self::$repository->update($accountRequest);

        $accountRequest->id = 3;
        $accountRequest->userGroupsView = [1, 2, 3];

        self::$repository->update($accountRequest);
    }

    /**
     * Comprobar la actualización de grupos de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdateEdit()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->userGroupsEdit = [2, 3];

        $this->assertEquals(3, self::$repository->updateEdit($accountRequest));

        $result = self::$repository->getUserGroupsByAccountId($accountRequest->id);
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertInstanceOf(ItemData::class, $data[0]);
        $this->assertEquals(1, (int)$data[0]->isEdit);
        $this->assertInstanceOf(ItemData::class, $data[1]);
        $this->assertEquals(1, (int)$data[1]->isEdit);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->userGroupsEdit = [10];

        self::$repository->updateEdit($accountRequest);

        // Comprobar que se lanza excepción al añadir usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->userGroupsEdit = [2, 3];

        self::$repository->updateEdit($accountRequest);
    }

    /**
     * Comprobar la eliminación de grupos de usuarios por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByAccountId()
    {
        $this->assertEquals(1, self::$repository->deleteByAccountId(1));
        $this->assertEquals(0, self::$repository->getUserGroupsByAccountId(1)->getNumRows());

        $this->assertEquals(0, self::$repository->deleteByAccountId(10));

        $this->assertEquals(1, $this->conn->getRowCount('AccountToUserGroup'));
    }

    /**
     * Comprobar la insercción de grupos de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAddEdit()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->userGroupsEdit = [1, 2, 3];

        self::$repository->addEdit($accountRequest);

        $result = self::$repository->getUserGroupsByAccountId($accountRequest->id);
        $data = $result->getDataAsArray();

        $this->assertEquals(3, $result->getNumRows());
        $this->assertInstanceOf(ItemData::class, $data[0]);
        $this->assertInstanceOf(ItemData::class, $data[1]);
        $this->assertInstanceOf(ItemData::class, $data[2]);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->userGroupsEdit = [10];

        self::$repository->addEdit($accountRequest);

        // Comprobar que se lanza excepción al añadir grupos de usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->userGroupsEdit = [1, 2, 3];

        self::$repository->addEdit($accountRequest);
    }

    /**
     * Comprobar la insercción de grupos de usuarios por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAdd()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 2;
        $accountRequest->userGroupsView = [1, 2, 3];

        $this->assertEquals(3, self::$repository->add($accountRequest));

        $result = self::$repository->getUserGroupsByAccountId($accountRequest->id);

        $this->assertEquals(3, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(3, $data);
        $this->assertInstanceOf(ItemData::class, $data[0]);
        $this->assertInstanceOf(ItemData::class, $data[1]);
        $this->assertInstanceOf(ItemData::class, $data[2]);

        $this->expectException(ConstraintException::class);

        // Comprobar que se lanza excepción al añadir usuarios no existentes
        $accountRequest->userGroupsView = [10];

        self::$repository->add($accountRequest);

        // Comprobar que se lanza excepción al añadir grupos de usuarios a cuenta no existente
        $accountRequest->id = 3;
        $accountRequest->userGroupsView = [1, 2, 3];

        self::$repository->add($accountRequest);
    }

    /**
     * Comprobar la eliminación de grupos de usuarios con permisos de modificación por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteEditByAccountId()
    {
        $this->assertEquals(1, self::$repository->deleteEditByAccountId(1));
        $this->assertEquals(0, self::$repository->getUserGroupsByAccountId(1)->getNumRows());

        $this->assertEquals(0, self::$repository->deleteEditByAccountId(10));

        $this->assertEquals(1, $this->conn->getRowCount('AccountToUserGroup'));
    }

    /**
     * Comprobar la obtención de grupos de usuarios por Id de grupo
     *
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetUserGroupsByUserGroupId()
    {
        $this->assertEquals(2, self::$repository->getUserGroupsByUserGroupId(2)->getNumRows());

        $this->assertEquals(0, self::$repository->getUserGroupsByUserGroupId(3)->getNumRows());

        $this->assertEquals(0, self::$repository->getUserGroupsByUserGroupId(10)->getNumRows());
    }

    /**
     * Comprobar la eliminación de grupos de usuarios por Id de grupo
     *
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByUserGroupId()
    {
        $this->assertEquals(2, self::$repository->deleteByUserGroupId(2));

        $this->assertEquals(0, self::$repository->deleteByUserGroupId(1));

        $this->assertEquals(0, self::$repository->deleteByUserGroupId(10));
    }
}
