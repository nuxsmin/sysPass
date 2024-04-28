<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SPT\Domain\Auth\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Application;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Ports\LdapAuthService;
use SP\Domain\Auth\Providers\Browser\BrowserAuthData;
use SP\Domain\Auth\Providers\Database\DatabaseAuthData;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\LoginAuthHandler;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Auth\Providers\Ldap\LdapAuthData;
use SP\Domain\Auth\Providers\Ldap\LdapCodeEnum;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserLoginRequest;
use SP\Domain\User\Ports\UserService;
use SPT\UnitaryTestCase;

/**
 * Class LoginAuthHandlerTest
 */
#[Group('unitary')]
class LoginAuthHandlerTest extends UnitaryTestCase
{

    private TrackService|MockObject   $trackService;
    private RequestService|MockObject $request;
    private UserService|MockObject    $userService;
    private LoginAuthHandler            $loginAuthHandler;

    public static function authLdapDataProvider(): array
    {
        return [
            [true, 'updateOnLogin'],
            [false, 'createOnLogin'],
        ];
    }

    /**
     * @throws AuthException
     */
    public function testAuthDatabase()
    {
        $authData = new DatabaseAuthData(true);
        $authData->success();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginAuthHandler->authDatabase($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthDatabaseWithFailAndNoAuthoritative()
    {
        $authData = new DatabaseAuthData(false);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginAuthHandler->authDatabase($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthDatabaseWithException()
    {
        $authData = new DatabaseAuthData(true);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Wrong login');

        $this->loginAuthHandler->authDatabase($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthDatabaseWithNoAuthoritative()
    {
        $authData = new DatabaseAuthData(false);
        $authData->success();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginAuthHandler->authDatabase($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthBrowserWithAuthBasic()
    {
        $authData = new BrowserAuthData(true);
        $authData->success();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->once())
            ->method('getServer')
            ->with('AUTH_TYPE')
            ->willReturn('test');

        $this->userService
            ->expects($this->once())
            ->method('checkExistsByLogin')
            ->with('a_user')
            ->willReturn(false);

        $this->userService
            ->expects($this->once())
            ->method('createOnLogin')
            ->with(
                self::callback(static function (UserLoginRequest $userLoginRequest) {
                    return $userLoginRequest->getLogin() === 'a_user'
                           && $userLoginRequest->getPassword() === 'a_password';
                })
            );

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginAuthHandler->authBrowser($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthBrowserWithAuthBasicAndDatabaseException()
    {
        $authData = new BrowserAuthData(true);
        $authData->success();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->once())
            ->method('getServer')
            ->with('AUTH_TYPE')
            ->willReturn('test');

        $this->userService
            ->expects($this->once())
            ->method('checkExistsByLogin')
            ->with('a_user')
            ->willReturn(false);

        $this->userService
            ->expects($this->once())
            ->method('createOnLogin')
            ->willThrowException(QueryException::error('test'));

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Internal error');

        $this->loginAuthHandler->authBrowser($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testAuthBrowserWithNoAuthBasic()
    {
        $authData = new BrowserAuthData(true);
        $authData->success();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $configData = new ConfigData();
        $configData->setAuthBasicAutoLoginEnabled(false);

        $config = self::createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        $application = new Application($config, $this->createStub(EventDispatcherInterface::class), $this->context);

        $loginAuthHandler = new LoginAuthHandler(
            $application,
            $this->trackService,
            $this->request,
            $this->userService
        );

        $this->request
            ->expects($this->once())
            ->method('getServer')
            ->with('AUTH_TYPE')
            ->willReturn('test');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->userService
            ->expects($this->never())
            ->method('createOnLogin');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $loginAuthHandler->authBrowser($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthBrowserWithFailAndNoAuthoritative()
    {
        $authData = new BrowserAuthData(false);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->once())
            ->method('getServer')
            ->with('AUTH_TYPE')
            ->willReturn('test');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->userService
            ->expects($this->never())
            ->method('createOnLogin');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginAuthHandler->authBrowser($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthBrowserWithFailAndAuthoritative()
    {
        $authData = new BrowserAuthData(true);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->once())
            ->method('getServer')
            ->with('AUTH_TYPE')
            ->willReturn('test');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->userService
            ->expects($this->never())
            ->method('createOnLogin');

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Wrong login');

        $this->loginAuthHandler->authBrowser($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    #[DataProvider('authLdapDataProvider')]
    public function testAuthLdap(bool $userExists, string $userMethod)
    {
        $authData = new LdapAuthData(true);
        $authData->setEmail('a_email');
        $authData->setName('a_username');
        $authData->success();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->userService
            ->expects($this->once())
            ->method('checkExistsByLogin')
            ->with('a_user')
            ->willReturn($userExists);

        $this->userService
            ->expects($this->once())
            ->method($userMethod)
            ->with(
                self::callback(static function (UserLoginRequest $userLoginRequest) {
                    return $userLoginRequest->getLogin() === 'a_user'
                           && $userLoginRequest->getPassword() === 'a_password'
                           && $userLoginRequest->getName() === 'a_username'
                           && $userLoginRequest->getEmail() === 'a_email'
                           && $userLoginRequest->getisLdap() === true;
                })
            );

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginAuthHandler->authLdap($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthLdapWithFailAndNoAuthoritative()
    {
        $authData = new LdapAuthData(false);
        $authData->setEmail('a_email');
        $authData->setName('a_username');
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginAuthHandler->authLdap($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthLdapWithFailAndAuthoritative()
    {
        $authData = new LdapAuthData(true);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Internal error');

        $this->loginAuthHandler->authLdap($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthLdapWithFailAndInvalidCredentials()
    {
        $authData = new LdapAuthData(true);
        $authData->setStatusCode(LdapCodeEnum::INVALID_CREDENTIALS->value);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Wrong login');

        $this->loginAuthHandler->authLdap($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthLdapWithFailAndExpired()
    {
        $authData = new LdapAuthData(true);
        $authData->setStatusCode(LdapAuthService::ACCOUNT_EXPIRED);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Account expired');

        $this->loginAuthHandler->authLdap($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    public function testAuthLdapWithFailAndNoGroups()
    {
        $authData = new LdapAuthData(true);
        $authData->setStatusCode(LdapAuthService::ACCOUNT_NO_GROUPS);
        $authData->fail();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->userService
            ->expects($this->never())
            ->method('checkExistsByLogin');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('User has no associated groups');

        $this->loginAuthHandler->authLdap($authData, $userLoginDto);
    }

    /**
     * @throws AuthException
     */
    #[DataProvider('authLdapDataProvider')]
    public function testAuthLdapWithDatabaseException(bool $userExists, string $userMethod)
    {
        $authData = new LdapAuthData(true);
        $authData->success();
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->userService
            ->expects($this->once())
            ->method('checkExistsByLogin')
            ->with('a_user')
            ->willReturn($userExists);

        $this->userService
            ->expects($this->once())
            ->method($userMethod)
            ->willThrowException(QueryException::error('test'));

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Internal error');

        $this->loginAuthHandler->authLdap($authData, $userLoginDto);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trackService = $this->createMock(TrackService::class);
        $this->trackService
            ->expects($this->atLeast(1))
            ->method('buildTrackRequest')
            ->with(LoginAuthHandler::class)
            ->willReturn(
                new TrackRequest(
                    self::$faker->unixTime(),
                    self::$faker->colorName(),
                    self::$faker->ipv4(),
                    self::$faker->randomNumber(2)
                )
            );

        $this->request = $this->createMock(RequestService::class);
        $this->userService = $this->createMock(UserService::class);

        $this->loginAuthHandler = new LoginAuthHandler(
            $this->application,
            $this->trackService,
            $this->request,
            $this->userService
        );
    }
}
