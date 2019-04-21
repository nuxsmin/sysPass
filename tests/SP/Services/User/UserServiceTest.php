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

namespace SP\Tests\SP\Services\User;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Config\ConfigData;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\ServiceException;
use SP\Services\User\UserLoginRequest;
use SP\Services\User\UserService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class UserServiceTest
 *
 * @package SP\Tests\SP\Services\User
 */
class UserServiceTest extends DatabaseTestCase
{
    const CURRENT_MASTERPASS = '12345678900';

    /**
     * @var ConfigData
     */
    private static $configData;

    /**
     * @var UserService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     * @throws SPException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_user.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(UserService::class);

        self::$configData = $dic->get(ConfigData::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $data = self::$service->getAllBasic();

        $this->assertCount(4, $data);
        $this->assertInstanceOf(UserData::class, $data[0]);
        $this->assertEquals('admin', $data[0]->getLogin());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageForUser()
    {
        $this->assertCount(2, self::$service->getUsageForUser(2));
    }

    /**
     * @throws SPException
     */
    public function testCreateOnLogin()
    {
        $data = new UserLoginRequest();
        $data->setName('Test SSO User');
        $data->setLogin('test_sso');
        $data->setEmail('test_sso@email.com');
        $data->setPassword('test123sso');

        $result = self::$service->createOnLogin($data);

        $this->assertEquals(5, $result);

        /** @var UserData $resultData */
        $resultData = self::$service->getById($result);

        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals($data->getLogin(), $resultData->getLogin());
        $this->assertEquals($data->getEmail(), $resultData->getEmail());
        $this->assertEquals(0, $resultData->isLdap());
        $this->assertTrue(Hash::checkHashKey($data->getPassword(), $resultData->getPass()));
        $this->assertEquals(self::$configData->getSsoDefaultGroup(), $resultData->getUserGroupId());
        $this->assertEquals(self::$configData->getSsoDefaultProfile(), $resultData->getUserProfileId());

        $data = new UserLoginRequest();
        $data->setName('Test LDAP User');
        $data->setLogin('test_ldap');
        $data->setEmail('test_ldap@email.com');
        $data->setPassword('test123ldap');
        $data->setIsLdap(1);

        $result = self::$service->createOnLogin($data);

        $this->assertEquals(6, $result);

        /** @var UserData $resultData */
        $resultData = self::$service->getById($result);

        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals($data->getLogin(), $resultData->getLogin());
        $this->assertEquals($data->getEmail(), $resultData->getEmail());
        $this->assertEquals(1, $resultData->isLdap());
        $this->assertTrue(Hash::checkHashKey($data->getPassword(), $resultData->getPass()));
        $this->assertEquals(self::$configData->getLdapDefaultGroup(), $resultData->getUserGroupId());
        $this->assertEquals(self::$configData->getLdapDefaultProfile(), $resultData->getUserProfileId());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function testUpdate()
    {
        $data = new UserData();
        $data->setId(2);
        $data->setName('Test User');
        $data->setLogin('test');
        $data->setEmail('test@syspass.org');
        $data->setNotes('Notes...');
        $data->setUserGroupId(3);
        $data->setUserProfileId(3);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);

        self::$service->update($data);

        /** @var UserData $resultData */
        $resultData = self::$service->getById(2);

        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals($data->getLogin(), $resultData->getLogin());
        $this->assertEquals($data->getEmail(), $resultData->getEmail());
        $this->assertEquals($data->getNotes(), $resultData->getNotes());
        $this->assertEquals($data->isLdap(), $resultData->isLdap());
        $this->assertEquals($data->getUserGroupId(), $resultData->getUserGroupId());
        $this->assertEquals($data->getUserProfileId(), $resultData->getUserProfileId());
        $this->assertEquals($data->isAdminApp(), $resultData->isAdminApp());
        $this->assertEquals($data->isAdminAcc(), $resultData->isAdminAcc());
        $this->assertEquals($data->isDisabled(), $resultData->isDisabled());
        $this->assertEquals($data->isChangePass(), $resultData->isChangePass());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function testUpdateDuplicatedLogin()
    {
        $data = new UserData();
        $data->setId(2);
        $data->setName('Test User');
        $data->setLogin('user_a');
        $data->setEmail('test@syspass.org');
        $data->setNotes('Notes...');
        $data->setUserGroupId(3);
        $data->setUserProfileId(3);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);

        $this->expectException(DuplicatedItemException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function testUpdateDuplicatedEmail()
    {
        $data = new UserData();
        $data->setId(2);
        $data->setName('Test User');
        $data->setLogin('test');
        $data->setEmail('user_a@syspass.org');
        $data->setNotes('Notes...');
        $data->setUserGroupId(3);
        $data->setUserProfileId(3);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);

        $this->expectException(DuplicatedItemException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function testUpdateUnknown()
    {
        $data = new UserData();
        $data->setId(10);
        $data->setName('Test User');
        $data->setLogin('test');
        $data->setEmail('test@syspass.org');
        $data->setNotes('Notes...');
        $data->setUserGroupId(3);
        $data->setUserProfileId(3);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);

        $this->expectException(ServiceException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function testUpdateNull()
    {
        $data = new UserData();
        $data->setId(2);

        $this->expectException(ConstraintException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws SPException
     */
    public function testUpdatePass()
    {
        self::$service->updatePass(2, 'test123');

        $data = self::$service->getById(2);

        $this->assertTrue(Hash::checkHashKey('test123', $data->getPass()));
        $this->assertEquals(0, $data->isChangePass());
        $this->assertEquals(1, $data->isChangedPass());

        $this->expectException(ServiceException::class);

        self::$service->updatePass(10, 'test123');
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('User A');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(1, $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(3, $data[0]->id);
        $this->assertEquals('User A', $data[0]->name);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('test');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$service->deleteByIdBatch([3, 4]));

        $this->expectException(ConstraintException::class);

        self::$service->deleteByIdBatch([1, 2]);
    }

    /**
     * @throws CryptoException
     * @throws SPException
     */
    public function testCreateWithMasterPass()
    {
        $data = new UserData();
        $data->setName('Test User');
        $data->setLogin('test');
        $data->setEmail('test@syspass.org');
        $data->setNotes('Test notes');
        $data->setUserGroupId(1);
        $data->setUserProfileId(1);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);

        $result = self::$service->createWithMasterPass($data, 'test123', self::CURRENT_MASTERPASS);

        /** @var UserData $resultData */
        $resultData = self::$service->getById($result);

        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals($data->getLogin(), $resultData->getLogin());
        $this->assertEquals($data->getEmail(), $resultData->getEmail());
        $this->assertEquals($data->getNotes(), $resultData->getNotes());
        $this->assertEquals($data->isLdap(), $resultData->isLdap());
        $this->assertEquals($data->getUserGroupId(), $resultData->getUserGroupId());
        $this->assertEquals($data->getUserProfileId(), $resultData->getUserProfileId());
        $this->assertEquals($data->isAdminApp(), $resultData->isAdminApp());
        $this->assertEquals($data->isAdminAcc(), $resultData->isAdminAcc());
        $this->assertEquals($data->isDisabled(), $resultData->isDisabled());
        $this->assertEquals($data->isChangePass(), $resultData->isChangePass());
        $this->assertEquals($data->isLdap(), $resultData->isLdap());
        $this->assertTrue(Hash::checkHashKey('test123', $resultData->getPass()));
        $this->assertNotEmpty($resultData->getMPass());
        $this->assertNotEmpty($resultData->getMKey());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUserEmailForGroup()
    {
        $this->assertCount(4, self::$service->getUserEmailForGroup(2));

        $this->assertCount(0, self::$service->getUserEmailForGroup(10));
    }

    /**
     * @throws SPException
     */
    public function testGetByLogin()
    {
        $data = self::$service->getByLogin('demo');

        $this->assertInstanceOf(UserData::class, $data);
        $this->assertEquals('sysPass demo', $data->getName());
        $this->assertEquals('demo', $data->getLogin());
        $this->assertEquals('demo', $data->getSsoLogin());
        $this->assertEquals('demo@syspass.org', $data->getEmail());
        $this->assertEquals('aaaa', $data->getNotes());
        $this->assertEquals('2018-04-01 21:29:47', $data->getLastLogin());
        $this->assertEquals('2018-04-14 08:47:43', $data->getLastUpdate());
        $this->assertEquals(1522582852, $data->getLastUpdateMPass());
        $this->assertEquals('Demo', $data->getUserGroupName());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals(2, $data->getUserProfileId());
        $this->assertEquals(0, $data->isAdminApp());
        $this->assertEquals(0, $data->isAdminAcc());
        $this->assertEquals(0, $data->isLdap());
        $this->assertEquals(0, $data->isDisabled());
        $this->assertEquals(0, $data->isMigrate());
        $this->assertEquals(0, $data->isChangePass());
        $this->assertEquals(0, $data->isChangedPass());
        $this->assertNotEmpty($data->getPass());
        $this->assertNotEmpty($data->getMPass());
        $this->assertNotEmpty($data->getMKey());

        $this->expectException(NoSuchItemException::class);

        self::$service->getByLogin('test');
    }

    /**
     * @throws SPException
     */
    public function testCreate()
    {
        $data = new UserData();
        $data->setName('Test User');
        $data->setLogin('test');
        $data->setEmail('test@syspass.org');
        $data->setNotes('Test notes');
        $data->setUserGroupId(1);
        $data->setUserProfileId(1);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);
        $data->setPass('test123');

        $result = self::$service->create($data);

        /** @var UserData $resultData */
        $resultData = self::$service->getById($result);

        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals($data->getLogin(), $resultData->getLogin());
        $this->assertEquals($data->getEmail(), $resultData->getEmail());
        $this->assertEquals($data->getNotes(), $resultData->getNotes());
        $this->assertEquals($data->isLdap(), $resultData->isLdap());
        $this->assertEquals($data->getUserGroupId(), $resultData->getUserGroupId());
        $this->assertEquals($data->getUserProfileId(), $resultData->getUserProfileId());
        $this->assertEquals($data->isAdminApp(), $resultData->isAdminApp());
        $this->assertEquals($data->isAdminAcc(), $resultData->isAdminAcc());
        $this->assertEquals($data->isDisabled(), $resultData->isDisabled());
        $this->assertEquals($data->isChangePass(), $resultData->isChangePass());
        $this->assertEquals($data->isLdap(), $resultData->isLdap());
        $this->assertTrue(Hash::checkHashKey('test123', $resultData->getPass()));
        $this->assertNull($data->getMPass());
        $this->assertNull($data->getMKey());
    }

    /**
     * @throws SPException
     */
    public function testCreateDuplicatedLogin()
    {
        $data = new UserData();
        $data->setName('Test User');
        $data->setLogin('demo');
        $data->setEmail('test@syspass.org');
        $data->setNotes('Test notes');
        $data->setUserGroupId(1);
        $data->setUserProfileId(1);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);
        $data->setPass('test123');

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }

    /**
     * @throws SPException
     */
    public function testCreateDuplicatedEmail()
    {
        $data = new UserData();
        $data->setName('Test User');
        $data->setLogin('test');
        $data->setEmail('demo@syspass.org');
        $data->setNotes('Test notes');
        $data->setUserGroupId(1);
        $data->setUserProfileId(1);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);
        $data->setPass('test123');

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }

    /**
     * @throws SPException
     */
    public function testCreateNull()
    {
        $data = new UserData();
        $data->setName('Test User');
        $data->setNotes('Test notes');
        $data->setUserGroupId(1);
        $data->setUserProfileId(1);
        $data->setIsAdminApp(1);
        $data->setIsAdminAcc(1);
        $data->setIsDisabled(1);
        $data->setIsChangePass(1);
        $data->setIsLdap(0);
        $data->setPass('test123');

        $this->expectException(ConstraintException::class);

        self::$service->create($data);
    }

    /**
     * @throws SPException
     */
    public function testGetById()
    {
        $data = self::$service->getById(2);

        $this->assertInstanceOf(UserData::class, $data);
        $this->assertEquals('sysPass demo', $data->getName());
        $this->assertEquals('demo', $data->getLogin());
        $this->assertEquals('demo', $data->getSsoLogin());
        $this->assertEquals('demo@syspass.org', $data->getEmail());
        $this->assertEquals('aaaa', $data->getNotes());
        $this->assertEquals('2018-04-01 21:29:47', $data->getLastLogin());
        $this->assertEquals('2018-04-14 08:47:43', $data->getLastUpdate());
        $this->assertEquals(1522582852, $data->getLastUpdateMPass());
        $this->assertEquals('Demo', $data->getUserGroupName());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals(2, $data->getUserProfileId());
        $this->assertEquals(0, $data->isAdminApp());
        $this->assertEquals(0, $data->isAdminAcc());
        $this->assertEquals(0, $data->isLdap());
        $this->assertEquals(0, $data->isDisabled());
        $this->assertEquals(0, $data->isMigrate());
        $this->assertEquals(0, $data->isChangePass());
        $this->assertEquals(0, $data->isChangedPass());
        $this->assertNotEmpty($data->getPass());
        $this->assertNotEmpty($data->getMPass());
        $this->assertNotEmpty($data->getMKey());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdatePreferencesById()
    {
        $data = new UserPreferencesData();
        $data->setLang('es_ES');
        $data->setAccountLink(true);
        $data->setOptionalActions(true);
        $data->setResultsAsCards(true);
        $data->setResultsPerPage(10);
        $data->setTopNavbar(true);
        $data->setTheme('theme');

        $this->assertEquals(1, self::$service->updatePreferencesById(2, $data));

        $resultData = self::$service->getById(2);

        $this->assertNotEmpty($resultData->getPreferences());

        $this->assertEquals($data, UserService::getUserPreferences($resultData->getPreferences()));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testUpdateLastLoginById()
    {
        $this->assertEquals(1, self::$service->updateLastLoginById(2));

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals(0, self::$service->updateLastLoginById(10));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckExistsByLogin()
    {
        $this->assertTrue(self::$service->checkExistsByLogin('demo'));

        $this->assertFalse(self::$service->checkExistsByLogin('test'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDelete()
    {
        self::$service->delete(3);

        $this->assertEquals(3, $this->conn->getRowCount('User'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDeleteUsed()
    {
        $this->expectException(ConstraintException::class);

        self::$service->delete(1);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDeleteUnknown()
    {
        $this->expectException(NoSuchItemException::class);

        self::$service->delete(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdateOnLogin()
    {
        $data = new UserLoginRequest();
        $data->setName('prueba');
        $data->setEmail('prueba@syspass.org');
        $data->setIsLdap(1);
        $data->setLogin('demo');
        $data->setPassword('test123');

        $this->assertEquals(1, self::$service->updateOnLogin($data));

        /** @var UserData $resultData */
        $resultData = self::$service->getByLogin('demo');

        $this->assertEquals($data->getName(), $resultData->getName());
        $this->assertEquals($data->getLogin(), $resultData->getLogin());
        $this->assertEquals($data->getEmail(), $resultData->getEmail());
        $this->assertEquals($data->getisLdap(), $resultData->isLdap());
        $this->assertTrue(Hash::checkHashKey($data->getPassword(), $resultData->getPass()));

        $data->setLogin('demodedadae');

        $this->assertEquals(0, self::$service->updateOnLogin($data));
    }
}
