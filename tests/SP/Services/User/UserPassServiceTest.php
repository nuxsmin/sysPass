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

namespace SP\Tests\Services\User;

use Closure;
use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserLoginData;
use SP\Repositories\NoSuchItemException;
use SP\Services\User\UserLoginResponse;
use SP\Services\User\UserPassService;
use SP\Services\User\UserService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class UserPassServiceTest
 *
 * @package SP\Tests\Services\User
 */
class UserPassServiceTest extends DatabaseTestCase
{
    const CURRENT_MASTERPASS = '12345678900';
    const NEW_MASTERPASS = '00123456789';

    /**
     * @var Closure
     */
    private static $getUserLoginResponse;

    /**
     * @var UserPassService
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
        self::$service = $dic->get(UserPassService::class);

        self::$getUserLoginResponse = function ($login) use ($dic) {
            return UserService::mapUserLoginResponse($dic->get(UserService::class)->getByLogin($login));
        };
    }

    /**
     * @throws CryptoException
     * @throws SPException
     */
    public function testCreateMasterPass()
    {
        $result = self::$service->createMasterPass(self::NEW_MASTERPASS, 'admin', 'test123');

        $key = self::$service->makeKeyForUser('admin', 'test123');

        $this->assertEquals(self::NEW_MASTERPASS, Crypt::decrypt($result->getCryptMasterPass(), $result->getCryptSecuredKey(), $key));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testMigrateUserPassById()
    {
        self::$service->migrateUserPassById(2, '123');

        $this->expectException(NoSuchItemException::class);

        self::$service->migrateUserPassById(10, '123');
    }

    /**
     * @throws CryptoException
     * @throws SPException
     */
    public function testUpdateMasterPassFromOldPass()
    {
        $data = new UserLoginData();
        $data->setLoginUser('admin');
        $data->setLoginPass('debian');
        $data->setUserLoginResponse(self::$getUserLoginResponse->call($this, $data->getLoginUser()));

        $result = self::$service->updateMasterPassFromOldPass('debian', $data);

        $this->assertEquals(UserPassService::MPASS_OK, $result->getStatus());

        $result = self::$service->updateMasterPassFromOldPass('test123', $data);

        $this->assertEquals(UserPassService::MPASS_WRONG, $result->getStatus());
    }

    /**
     * @throws SPException
     */
    public function testLoadUserMPass()
    {
        $data = new UserLoginData();
        $data->setLoginUser('admin');
        $data->setLoginPass('debian');
        $data->setUserLoginResponse(self::$getUserLoginResponse->call($this, $data->getLoginUser()));

        $result = self::$service->loadUserMPass($data);

        $this->assertEquals(UserPassService::MPASS_OK, $result->getStatus());

        $result = self::$service->loadUserMPass($data, 'test123');

        $this->assertEquals(UserPassService::MPASS_CHECKOLD, $result->getStatus());
    }

    /**
     * @throws SPException
     */
    public function testLoadUserMPassOutdated()
    {
        $data = new UserLoginData();
        $data->setLoginUser('admin');
        $data->setLoginPass('debian');

        /** @var UserLoginResponse $userData */
        $userData = self::$getUserLoginResponse->call($this, $data->getLoginUser());
        $userData->setLastUpdateMPass(1521887150);

        $data->setUserLoginResponse($userData);

        $result = self::$service->loadUserMPass($data);

        $this->assertEquals(UserPassService::MPASS_CHANGED, $result->getStatus());
    }

    /**
     * @throws SPException
     */
    public function testLoadUserMPassChangedPass()
    {
        $data = new UserLoginData();
        $data->setLoginUser('admin');
        $data->setLoginPass('debian');

        /** @var UserLoginResponse $userData */
        $userData = self::$getUserLoginResponse->call($this, $data->getLoginUser());
        $userData->setIsChangedPass(true);

        $data->setUserLoginResponse($userData);

        $result = self::$service->loadUserMPass($data);

        $this->assertEquals(UserPassService::MPASS_CHECKOLD, $result->getStatus());
    }

    /**
     * @throws SPException
     */
    public function testLoadUserMPassNotSet()
    {
        $data = new UserLoginData();
        $data->setLoginUser('admin');
        $data->setLoginPass('debian');
        $data->setUserLoginResponse(new UserLoginResponse());

        $result = self::$service->loadUserMPass($data);

        $this->assertEquals(UserPassService::MPASS_NOTSET, $result->getStatus());
    }

    /**
     * @throws CryptoException
     * @throws SPException
     */
    public function testUpdateMasterPassOnLogin()
    {
        $data = new UserLoginData();
        $data->setLoginUser('admin');
        $data->setLoginPass('test123');
        $data->setUserLoginResponse(self::$getUserLoginResponse->call($this, $data->getLoginUser()));

        $result = self::$service->updateMasterPassOnLogin(self::CURRENT_MASTERPASS, $data);

        $this->assertEquals(UserPassService::MPASS_OK, $result->getStatus());

        $result = self::$service->updateMasterPassOnLogin(self::NEW_MASTERPASS, $data);

        $this->assertEquals(UserPassService::MPASS_WRONG, $result->getStatus());
    }
}
