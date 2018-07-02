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

namespace SP\Tests\Services;

use SP\Account\AccountRequest;
use SP\Core\Crypt\Crypt;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountPasswordRequest;
use SP\Services\Account\AccountService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountServiceTest
 *
 * @package SP\Tests\Services
 */
class AccountServiceTest extends DatabaseTestCase
{
    const SECURE_KEY_PASSWORD = '12345678900';
    /**
     * @var AccountService
     */
    private static $service;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_account.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(AccountService::class);
    }

    /**
     * @covers \SP\Services\Account\AccountService::withTagsById()
     * @covers \SP\Services\Account\AccountService::withUsersById()
     * @covers \SP\Services\Account\AccountService::withUserGroupsById()
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function testCreate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->name = 'Prueba 2';
        $accountRequest->login = 'admin';
        $accountRequest->url = 'http://syspass.org';
        $accountRequest->notes = 'notas';
        $accountRequest->userEditId = 1;
        $accountRequest->passDateChange = time() + 3600;
        $accountRequest->clientId = 1;
        $accountRequest->categoryId = 1;
        $accountRequest->isPrivate = 0;
        $accountRequest->isPrivateGroup = 0;
        $accountRequest->parentId = 0;
        $accountRequest->userId = 1;
        $accountRequest->userGroupId = 2;
        $accountRequest->pass = '1234abc';
        $accountRequest->tags = [2, 3];
        $accountRequest->usersView = [2, 4];
        $accountRequest->usersEdit = [3, 4];
        $accountRequest->userGroupsView = [2, 3];
        $accountRequest->userGroupsEdit = [2];

        $this->assertEquals(3, self::$service->create($accountRequest));

        $result = self::$service->getById(3);

        self::$service->withTagsById($result);
        self::$service->withUsersById($result);
        self::$service->withUserGroupsById($result);

        $data = $result->getAccountVData();

        $this->assertEquals(3, $result->getId());
        $this->assertEquals($accountRequest->name, $data->getName());
        $this->assertEquals($accountRequest->login, $data->getLogin());
        $this->assertEquals($accountRequest->url, $data->getUrl());
        $this->assertEquals($accountRequest->notes, $data->getNotes());
        $this->assertEquals($accountRequest->userId, $data->getUserId());
        $this->assertEquals($accountRequest->userGroupId, $data->getUserGroupId());
        $this->assertEquals($accountRequest->userEditId, $data->getUserEditId());
        $this->assertEquals($accountRequest->passDateChange, $data->getPassDateChange());
        $this->assertEquals($accountRequest->clientId, $data->getClientId());
        $this->assertEquals($accountRequest->categoryId, $data->getCategoryId());
        $this->assertEquals($accountRequest->isPrivate, $data->getIsPrivate());
        $this->assertEquals($accountRequest->isPrivateGroup, $data->getIsPrivateGroup());
        $this->assertEquals($accountRequest->parentId, $data->getParentId());

        $tags = $result->getTags();

        $this->assertEquals(3, $tags[0]->getId());
        $this->assertEquals(2, $tags[1]->getId());

        $users = $result->getUsers();

        $this->assertEquals(2, $users[0]->getId());
        $this->assertEquals(0, (int)$users[0]->isEdit);
        $this->assertEquals(3, $users[1]->getId());
        $this->assertEquals(1, (int)$users[1]->isEdit);
        $this->assertEquals(4, $users[2]->getId());
        $this->assertEquals(1, (int)$users[2]->isEdit);

        $groups = $result->getUserGroups();

        $this->assertEquals(2, $groups[0]->getId());
        $this->assertEquals(1, (int)$groups[0]->isEdit);
        $this->assertEquals(3, $groups[1]->getId());
        $this->assertEquals(0, (int)$groups[1]->isEdit);

        $data = self::$service->getPasswordForId(3);

        $this->assertEquals('1234abc', Crypt::decrypt($data->getPass(), $data->getKey(), self::SECURE_KEY_PASSWORD));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function testDelete()
    {
        // Comprobar registros iniciales
        $this->assertEquals(2, $this->conn->getRowCount('Account'));

        // Eliminar registros y comprobar el total de registros
        self::$service->delete(1);
        self::$service->delete(2);

        $this->assertEquals(0, $this->conn->getRowCount('Account'));

        $this->expectException(NoSuchItemException::class);

        // Eliminar un registro no existente
        $this->assertEquals(0, self::$service->delete(100));
    }

    /**
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws NoSuchItemException
     */
    public function testUpdatePasswordMasterPass()
    {
        $accountRequest = new AccountPasswordRequest();
        $accountRequest->id = 2;
        $accountRequest->key = Crypt::makeSecuredKey(self::SECURE_KEY_PASSWORD);
        $accountRequest->pass = Crypt::encrypt('1234', $accountRequest->key, self::SECURE_KEY_PASSWORD);

        // Comprobar que la modificación de la clave es correcta
        $this->assertTrue(self::$service->updatePasswordMasterPass($accountRequest));

        $data = self::$service->getPasswordForId(2);
        $clearPassword = Crypt::decrypt($data->pass, $data->key, self::SECURE_KEY_PASSWORD);

        // Comprobar que la clave obtenida es igual a la encriptada anteriormente
        $this->assertEquals('1234', $clearPassword);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetTotalNumAccounts()
    {
        $this->assertEquals(2, self::$service->getTotalNumAccounts());
    }

    /**
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetDataForLink()
    {
        self::$service->getDataForLink(1);
    }

    public function testGetAccountsPassData()
    {

    }

    public function testEditRestore()
    {

    }

    public function testGetLinked()
    {

    }

    public function testGetPasswordEncrypted()
    {

    }

    public function testEditPassword()
    {

    }

    public function testGetPasswordForId()
    {

    }

    public function testUpdate()
    {

    }

    public function testGetForUser()
    {

    }

    public function testGetById()
    {

    }

    public function testDeleteByIdBatch()
    {

    }

    public function testGetByFilter()
    {

    }

    public function testSearch()
    {

    }

    public function testIncrementDecryptCounter()
    {

    }

    public function testIncrementViewCounter()
    {

    }

    public function testGetPasswordHistoryForId()
    {

    }

    public function testGetAllBasic()
    {

    }
}
