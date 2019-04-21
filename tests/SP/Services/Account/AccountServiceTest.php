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

namespace SP\Tests\Services\Account;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountData;
use SP\DataModel\AccountSearchVData;
use SP\DataModel\AccountVData;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountBulkRequest;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountPasswordRequest;
use SP\Services\Account\AccountRequest;
use SP\Services\Account\AccountSearchFilter;
use SP\Services\Account\AccountService;
use SP\Services\ServiceException;
use SP\Services\User\UserLoginResponse;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\PasswordUtil;
use stdClass;
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
     * @var AccountHistoryService
     */
    protected static $accountHistoryService;
    /**
     * @var ContextInterface
     */
    private static $context;
    /**
     * @var AccountService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_account.xml';

        self::$context = $dic->get(ContextInterface::class);

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(AccountService::class);
        self::$accountHistoryService = $dic->get(AccountHistoryService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws CryptoException
     * @throws NoSuchPropertyException
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
     * @throws ServiceException
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
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetTotalNumAccounts()
    {
        $this->assertEquals(7, self::$service->getTotalNumAccounts());
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetDataForLink()
    {
        $data = self::$service->getDataForLink(1);

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Google', $data->getName());
        $this->assertEquals('admin', $data->getLogin());
        $this->assertEquals(pack('H*', '6465663530323030656135663361636362366237656462653536343938666234313231616635323237363539663162346532383963386361346565323732656530636238333632316436393736353665373631393435623033353236616164333730336662306531333535626437333638653033666137623565633364306365323634663863643436393436633365353234316534373338376130393133663935303736396364613365313234643432306636393834386434613262316231306138'), $data->getPass());
        $this->assertEquals(pack('H*', '6465663130303030646566353032303065646434636466636231333437613739616166313734343462343839626362643364353664376664356562373233363235653130316261666432323539343633336664626639326630613135373461653562613562323535353230393236353237623863633534313862653363376361376536366139356366353366356162663031623064343236613234336162643533643837643239636633643165326532663732626664396433366133653061343534656664373134633661366237616338363966636263366435303166613964316338386365623264303861333438626633656638653135356538633865353838623938636465653061306463313835646636366535393138393831653366303464323139386236383738333539616563653034376434643637663835313235636661313237633138373865643530616630393434613934616363356265316130323566623065633362663831613933626365366365343734336164363562656638353131343466343332323837356438323339303236656363613866643862376330396563356465373233666466313636656166386336356539666537353436333535333664393766383366316366663931396530386339373730636166633136376661656364306366656262323931666334343831333238333662366432'), $data->getKey());
        $this->assertEquals('http://google.com', $data->getUrl());
        $this->assertEquals('aaaa', $data->getNotes());
        $this->assertEquals('Google', $data->getClientName());
        $this->assertEquals('Web', $data->getCategoryName());

        $this->expectException(NoSuchItemException::class);

        self::$service->getDataForLink(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAccountsPassData()
    {
        $this->assertCount(2, self::$service->getAccountsPassData());
    }

    /**
     * @throws Exception
     */
    public function testEditRestore()
    {
        self::$service->editRestore(3, 1);

        $this->expectException(ServiceException::class);

        self::$service->editRestore(1, 1);
        self::$service->editRestore(3, 10);

        $this->assertEquals(6, $this->conn->getRowCount('AccountHistory'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetLinked()
    {
        $result = self::$service->getLinked(1);

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]->id);
        $this->assertEquals('Apple', $result[0]->name);

        $this->assertCount(0, self::$service->getLinked(2));
    }

    /**
     * @throws ServiceException
     * @throws CryptoException
     */
    public function testGetPasswordEncrypted()
    {
        $data = self::$service->getPasswordEncrypted('123abc');

        $this->assertEquals('123abc', Crypt::decrypt($data['pass'], $data['key'], self::SECURE_KEY_PASSWORD));

        $randomKeyPass = PasswordUtil::generateRandomBytes();

        $data = self::$service->getPasswordEncrypted('123abc', $randomKeyPass);

        $this->assertEquals('123abc', Crypt::decrypt($data['pass'], $data['key'], $randomKeyPass));
    }

    /**
     * @throws NoSuchItemException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function testEditPassword()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->pass = '123abc';
        $accountRequest->id = 2;
        $accountRequest->userEditId = 1;
        $accountRequest->passDateChange = time() + 3600;

        // Comprobar que la modificación de la clave es correcta
        self::$service->editPassword($accountRequest);

        $data = self::$service->getPasswordForId(2);

        $clearPassword = Crypt::decrypt($data->pass, $data->key, self::SECURE_KEY_PASSWORD);

        // Comprobar que la clave obtenida es igual a la encriptada anteriormente
        $this->assertEquals('123abc', $clearPassword);

        $this->expectException(NoSuchItemException::class);

        // Comprobar que no devuelve resultados
        self::$service->getPasswordForId(10);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws Exception
     */
    public function testUpdate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->name = 'Prueba 1';
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
        $accountRequest->userGroupId = 2;
        $accountRequest->tags = [2, 3];
        $accountRequest->usersView = [2, 4];
        $accountRequest->usersEdit = [3, 4];
        $accountRequest->userGroupsView = [2, 3];
        $accountRequest->userGroupsEdit = [2];
        $accountRequest->updateTags = true;

        self::$service->update($accountRequest);

        $result = self::$service->getById(1);

        self::$service->withTagsById($result);
        self::$service->withUsersById($result);
        self::$service->withUserGroupsById($result);

        $data = $result->getAccountVData();

        $this->assertEquals(1, $result->getId());
        $this->assertEquals($accountRequest->name, $data->getName());
        $this->assertEquals($accountRequest->login, $data->getLogin());
        $this->assertEquals($accountRequest->url, $data->getUrl());
        $this->assertEquals($accountRequest->notes, $data->getNotes());
        $this->assertEquals(1, $data->getUserId());
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
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws Exception
     */
    public function testUpdateOwnerByAdmin()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->name = 'Prueba 1';
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
        $accountRequest->userId = 2;
        $accountRequest->userGroupId = 2;

        self::$service->update($accountRequest);

        $result = self::$service->getById(1);

        $data = $result->getAccountVData();

        $this->assertEquals(1, $result->getId());
        $this->assertEquals($accountRequest->userId, $data->getUserId());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws Exception
     */
    public function testUpdateOwnerByOwner()
    {
        $lastUserData = self::$context->getUserData();

        $userData = new UserLoginResponse();
        $userData->setId(1);
        $userData->setUserGroupId(1);
        $userData->setIsAdminApp(false);
        $userData->setIsAdminAcc(false);

        $userProfile = new ProfileData();
        $userProfile->setAccEdit(true);
        $userProfile->setAccPermission(true);

        self::$context->setUserData($userData);
        self::$context->setUserProfile($userProfile);

        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->name = 'Prueba 1';
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
        $accountRequest->userId = 2;
        $accountRequest->userGroupId = 2;

        self::$service->update($accountRequest);

        $result = self::$service->getById(1);

        $data = $result->getAccountVData();

        $this->assertEquals(1, $result->getId());
        $this->assertEquals($accountRequest->userId, $data->getUserId());
        $this->assertEquals($accountRequest->userGroupId, $data->getUserGroupId());

        self::$context->setUserData($lastUserData);
        self::$context->setUserProfile(new ProfileData());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws Exception
     */
    public function testUpdateOwnerByForeign()
    {
        $lastUserData = self::$context->getUserData();

        $userData = new UserLoginResponse();
        $userData->setId(2);
        $userData->setUserGroupId(2);
        $userData->setIsAdminApp(false);
        $userData->setIsAdminAcc(false);

        $userProfile = new ProfileData();
        $userProfile->setAccEdit(true);
        $userProfile->setAccPermission(true);

        self::$context->setUserData($userData);
        self::$context->setUserProfile($userProfile);

        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->name = 'Prueba 1';
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
        $accountRequest->userId = 2;
        $accountRequest->userGroupId = 2;

        self::$service->update($accountRequest);

        $result = self::$service->getById(1);

        $data = $result->getAccountVData();

        $this->assertEquals(1, $result->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(1, $data->getUserGroupId());

        self::$context->setUserData($lastUserData);
        self::$context->setUserProfile(new ProfileData());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws Exception
     */
    public function testUpdateGroupByGroup()
    {
        $lastUserData = self::$context->getUserData();

        $userData = new UserLoginResponse();
        $userData->setId(2);
        $userData->setUserGroupId(1);
        $userData->setIsAdminApp(false);
        $userData->setIsAdminAcc(false);

        $userProfile = new ProfileData();
        $userProfile->setAccEdit(true);
        $userProfile->setAccPermission(true);

        self::$context->setUserData($userData);
        self::$context->setUserProfile($userProfile);

        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->name = 'Prueba 1';
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
        $accountRequest->userId = 2;
        $accountRequest->userGroupId = 2;

        self::$service->update($accountRequest);

        $result = self::$service->getById(1);

        $data = $result->getAccountVData();

        $this->assertEquals(1, $result->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals($accountRequest->userGroupId, $data->getUserGroupId());

        self::$context->setUserData($lastUserData);
        self::$context->setUserProfile(new ProfileData());
    }

    /**
     * @throws Exception
     */
    public function testUpdateNotFound()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 3;

        $this->expectException(NoSuchItemException::class);

        self::$service->update($accountRequest);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForUser()
    {
        $this->assertCount(2, self::$service->getForUser());
        $this->assertCount(0, self::$service->getForUser(1));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        // Comprobar registros iniciales
        $this->assertEquals(2, $this->conn->getRowCount('Account'));

        self::$service->deleteByIdBatch([1, 2, 100]);

        // Comprobar registros tras eliminación
        $this->assertEquals(0, $this->conn->getRowCount('Account'));

        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([100]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetByFilter()
    {
        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCategoryId(1);

        // Comprobar un Id de categoría
        /** @var AccountSearchVData[] $result */
        $result = self::$service->getByFilter($searchFilter)->getDataAsArray();

        $this->assertCount(1, $result);

        // Comprobar un Id de categoría no existente
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCategoryId(10);

        /** @var AccountSearchVData[] $result */
        $result = self::$service->getByFilter($searchFilter)->getDataAsArray();

        $this->assertCount(0, $result);

        // Comprobar un Id de cliente
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setClientId(1);

        /** @var AccountSearchVData[] $result */
        $result = self::$service->getByFilter($searchFilter)->getDataAsArray();

        $this->assertCount(1, $result);

        // Comprobar un Id de cliente no existente
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setClientId(10);

        /** @var AccountSearchVData[] $result */
        $result = self::$service->getByFilter($searchFilter)->getDataAsArray();

        $this->assertCount(0, $result);

        // Comprobar una cadena de texto
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCleanTxtSearch('apple.com');

        /** @var AccountSearchVData[] $result */
        $result = self::$service->getByFilter($searchFilter)->getDataAsArray();

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]->getId());

        // Comprobar los favoritos
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setSearchFavorites(true);

        /** @var AccountSearchVData[] $result */
        $result = self::$service->getByFilter($searchFilter)->getDataAsArray();

        $this->assertCount(0, $result);

        // Comprobar las etiquetas
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setTagsId([1]);

        /** @var AccountSearchVData[] $result */
        $result = self::$service->getByFilter($searchFilter)->getDataAsArray();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->getId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        // Comprobar búsqueda con el texto Google Inc
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('Google');
        $itemSearchData->setLimitCount(10);

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(1, $data[0]->id);
        $this->assertEquals('Google', $data[0]->name);

        // Comprobar búsqueda con el texto Apple
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('Apple');
        $itemSearchData->setLimitCount(1);

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Apple', $data[0]->name);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testIncrementDecryptCounter()
    {
        /** @var AccountVData $accountBefore */
        $accountBefore = self::$service->getById(1)->getAccountVData();

        $this->assertTrue(self::$service->incrementDecryptCounter(1));

        /** @var AccountVData $accountAfter */
        $accountAfter = self::$service->getById(1)->getAccountVData();

        $this->assertEquals($accountBefore->getCountDecrypt() + 1, $accountAfter->getCountDecrypt());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testIncrementViewCounter()
    {
        /** @var AccountVData $accountBefore */
        $accountBefore = self::$service->getById(1)->getAccountVData();

        $this->assertTrue(self::$service->incrementViewCounter(1));

        /** @var AccountVData $accountAfter */
        $accountAfter = self::$service->getById(1)->getAccountVData();

        $this->assertEquals($accountBefore->getCountView() + 1, $accountAfter->getCountView());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetPasswordHistoryForId()
    {
        $data = self::$service->getPasswordHistoryForId(3);

        $this->assertEquals(3, $data->getId());
        $this->assertEquals('Google', $data->getName());
        $this->assertEquals('admin', $data->getLogin());
        $this->assertNull($data->getParentId());
        $this->assertEquals(pack('H*', '646566353032303064396362643366376662646536326637663732663861383732623430613839386131643134333933663662623033316664343362366461643762626564643634386437363964346634616234386638336636653236396166623734636261383134313363626162326461393733343934613231653934666331616664633637313732316562356666396562646132613665313937626233333563613632383830393934333863643731333230383132316430366433303838'), $data->getPass());
        $this->assertEquals(pack('H*', '6465663130303030646566353032303032636635623034396437656539356531653838663166613438643061616132663133613163663766346238316165663837326134373665316461653661353865316666626438346130383166303062633138646136373265653935643234626564336565303063333262646262303433336633356534323263616337613238363532336233313666316137333462616337343839346631333632643863376430373861373862396135633064396239653061353537626562666336636566623766363166376330393734356461623536373762303436313865343936383434663932666364303634316330303935636239363938336361336631363161623134663339643536636233653938333833613062396464356365383736333334376364363933313563306436343362623937366139383831376632346431303364316533353133306262393862353034353262346334663934663162323531383632356530653331346438343430323362666334306264616265376437386238663632326535353338636537663431626261616461613138646333333662623762636565333030656565333734616537356365303131363731323239383132383964346634383661376635303136303835336138663335653366393230383632386162373332343335633037656432616234'), $data->getKey());
        $this->assertEquals(pack('H*', '24327924313024787473754E325055766753482F306D7266426C73624F4163745667436A596371447143364C3354395172614E785A43345258475961'), $data->getMPassHash());

        $this->expectException(NoSuchItemException::class);

        self::$service->getPasswordHistoryForId(1);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $data = self::$service->getAllBasic();

        $this->assertCount(2, $data);
        $this->assertInstanceOf(AccountData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertInstanceOf(AccountData::class, $data[1]);
        $this->assertEquals(2, $data[1]->getId());
    }

    /**
     * @throws SPException
     */
    public function testCreateFromHistory()
    {
        $data = self::$accountHistoryService->getById(7);

        $this->assertEquals(3, self::$service->createFromHistory($data));

        $result = self::$service->getById(3);
        $resultData = $result->getAccountVData();

        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals($data->getCategoryId(), $resultData->getCategoryId());
        $this->assertEquals($data->getClientId(), $resultData->getClientId());
        $this->assertEquals($data->getUrl(), $resultData->getUrl());
        $this->assertEquals($data->getLogin(), $resultData->getLogin());
        $this->assertEquals($data->getNotes(), $resultData->getNotes());
        $this->assertEquals($data->getPassDateChange(), $resultData->getPassDateChange());
        $this->assertEquals($data->getUserId(), $resultData->getUserId());
        $this->assertEquals($data->getUserGroupId(), $resultData->getUserGroupId());
        $this->assertEquals($data->getParentId(), $resultData->getParentId());
        $this->assertEquals($data->getIsPrivate(), $resultData->getIsPrivate());
        $this->assertEquals($data->getIsPrivateGroup(), $resultData->getIsPrivateGroup());

        $resultData = self::$service->getPasswordForId(3);

        $this->assertEquals($data->getPass(), $resultData->getPass());
        $this->assertEquals($data->getKey(), $resultData->getKey());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testUpdateBulk()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->userEditId = 1;
        $accountRequest->passDateChange = time() + 3600;
        $accountRequest->clientId = 1;
        $accountRequest->categoryId = 1;
        $accountRequest->userId = 2;
        $accountRequest->userGroupId = 2;
        $accountRequest->tags = [2, 3];
        $accountRequest->usersView = [2, 4];
        $accountRequest->usersEdit = [3, 4];
        $accountRequest->userGroupsView = [2, 3];
        $accountRequest->userGroupsEdit = [2];

        $bulkRequest = new AccountBulkRequest([1, 2], $accountRequest);

        self::$service->updateBulk($bulkRequest);

        $this->checkBulkData($accountRequest, 1);
        $this->checkBulkData($accountRequest, 2);
    }

    /**
     * @param AccountRequest     $accountRequest
     * @param                    $accountId
     *
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    private function checkBulkData(AccountRequest $accountRequest, $accountId)
    {
        $result = self::$service->getById($accountId);

        self::$service->withTagsById($result);
        self::$service->withUsersById($result);
        self::$service->withUserGroupsById($result);

        $data = $result->getAccountVData();

        $this->assertEquals($accountId, $result->getId());
        $this->assertEquals($accountRequest->userEditId, $data->getUserEditId());
        $this->assertEquals($accountRequest->userId, $data->getUserId());
        $this->assertEquals($accountRequest->userGroupId, $data->getUserGroupId());
        $this->assertEquals($accountRequest->passDateChange, $data->getPassDateChange());
        $this->assertEquals($accountRequest->clientId, $data->getClientId());
        $this->assertEquals($accountRequest->categoryId, $data->getCategoryId());

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
    }
}
