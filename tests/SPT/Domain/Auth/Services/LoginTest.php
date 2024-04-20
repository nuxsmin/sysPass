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
use RuntimeException;
use SP\Core\Context\ContextException;
use SP\DataModel\ProfileData;
use SP\Domain\Auth\Dtos\LoginResponseDto;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Ports\LoginAuthHandlerService;
use SP\Domain\Auth\Ports\LoginMasterPassService;
use SP\Domain\Auth\Ports\LoginUserService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\Login;
use SP\Domain\Auth\Services\LoginStatus;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Ports\UserService;
use SP\Providers\Auth\AuthDataBase;
use SP\Providers\Auth\AuthProviderService;
use SP\Providers\Auth\AuthResult;
use SP\Providers\Auth\AuthType;
use SP\Providers\Auth\Browser\BrowserAuthData;
use SP\Providers\Auth\Database\DatabaseAuthData;
use SP\Providers\Auth\Ldap\LdapAuthData;
use SP\Providers\ProviderInterface;
use SPT\Generators\UserDataGenerator;
use SPT\Generators\UserProfileDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class LoginTest
 *
 * @property SessionContext|MockObject $context
 */
#[Group('unitary')]
class LoginTest extends UnitaryTestCase
{

    private TrackService|MockObject                          $trackService;
    private RequestInterface|MockObject                      $request;
    private MockObject|AuthProviderService|ProviderInterface $authProviderService;
    private MockObject|LanguageInterface                     $language;
    private UserService|MockObject                           $userService;
    private LoginUserService|MockObject                      $loginUserService;
    private MockObject|LoginMasterPassService                $loginMasterPassService;
    private MockObject|UserProfileService                    $userProfileService;
    private MockObject|LoginAuthHandlerService               $loginAuthHandlerService;
    private Login                                            $login;

    public static function authResultProviderAuthoritative(): array
    {
        $authResultDatabase = new AuthResult(AuthType::Database, (new DatabaseAuthData(true))->success());
        $authResultBrowser = new AuthResult(AuthType::Browser, (new BrowserAuthData(true))->success());
        $authResultLdap = new AuthResult(AuthType::Ldap, (new LdapAuthData(true))->success());

        return array_map(
            static fn(AuthResult $authResult) => [
                $authResult,
                $authResult->getAuthData(),
                $authResult->getAuthType()->value
            ],
            [
                $authResultDatabase,
                $authResultBrowser,
                $authResultLdap
            ]
        );
    }

    public static function authResultProviderNonAuthoritative(): array
    {
        $authResultDatabase = new AuthResult(AuthType::Database, (new DatabaseAuthData(false))->success());
        $authResultBrowser = new AuthResult(AuthType::Browser, (new BrowserAuthData(false))->success());
        $authResultLdap = new AuthResult(AuthType::Ldap, (new LdapAuthData(false))->success());

        return array_map(
            static fn(AuthResult $authResult) => [
                $authResult,
                $authResult->getAuthData(),
                $authResult->getAuthType()->value
            ],
            [
                $authResultDatabase,
                $authResultBrowser,
                $authResultLdap
            ]
        );
    }

    public static function loginInputProvider(): array
    {
        return [
            ['', 'a_pass'],
            ['a_user', '']
        ];
    }

    public static function loginStatusDataProvider(): array
    {
        return [
            [LoginStatus::INVALID_LOGIN],
            [LoginStatus::PASS_RESET_REQUIRED],
            [LoginStatus::USER_DISABLED],
            [LoginStatus::MAX_ATTEMPTS_EXCEEDED],
            [LoginStatus::INVALID_MASTER_PASS],
            [LoginStatus::OLD_PASS_REQUIRED],
            [LoginStatus::OK],
        ];
    }

    public static function fromDataProvider(): array
    {
        return [
            [null, 'index.php?r=index'],
            ['a_test', 'index.php?r=a_test'],
        ];
    }

    /**
     * @throws AuthException
     */
    #[DataProvider('authResultProviderAuthoritative')]
    #[DataProvider('authResultProviderNonAuthoritative')]
    public function testHandleAuthResponseWithTrue(
        AuthResult   $authResult,
        AuthDataBase $authDataBase,
        string       $targetMethod
    ) {
        $this->loginAuthHandlerService
            ->expects($this->once())
            ->method($targetMethod)
            ->with($authDataBase);

        $this->login->handleAuthResponse($authResult);
    }

    /**
     * @throws AuthException
     */
    #[DataProvider('authResultProviderAuthoritative')]
    #[DataProvider('authResultProviderNonAuthoritative')]
    public function testHandleAuthResponseWithFalse(
        AuthResult   $authResult,
        AuthDataBase $authDataBase,
        string       $targetMethod
    ) {
        $this->loginAuthHandlerService
            ->expects($this->once())
            ->method($targetMethod)
            ->with($authDataBase);

        $this->login->handleAuthResponse($authResult);
    }

    /**
     * @throws AuthException
     */
    #[DataProvider('fromDataProvider')]
    public function testDoLogin(?string $from, string $redirect)
    {
        $userDataDto = new UserDataDto(UserDataGenerator::factory()->buildUserData());

        $this->request
            ->expects($this->once())
            ->method('analyzeString')
            ->with('user')
            ->willReturn('a_user');

        $this->request
            ->expects($this->once())
            ->method('analyzeEncrypted')
            ->with('pass')
            ->willReturn('a_password');

        $this->trackService
            ->expects($this->once())
            ->method('checkTracking')
            ->willReturn(false);

        $this->authProviderService
            ->expects($this->once())
            ->method('doAuth')
            ->with(
                self::callback(function (UserLoginDto $userLoginDto) {
                    return $userLoginDto->getLoginUser() === 'a_user'
                           && $userLoginDto->getLoginPass() === 'a_password';
                }),
                self::callback(function (array $callable) {
                    return $callable[0] instanceof Login
                           && $callable[1] === 'handleAuthResponse';
                })
            )
            ->willReturn($userDataDto);

        $this->loginUserService
            ->expects($this->once())
            ->method('checkUser')
            ->with($userDataDto)
            ->willReturn(new LoginResponseDto(LoginStatus::PASS));

        $this->loginMasterPassService
            ->expects($this->once())
            ->method('loadMasterPass')
            ->with(
                self::callback(function (UserLoginDto $userLoginDto) {
                    return $userLoginDto->getLoginUser() === 'a_user'
                           && $userLoginDto->getLoginPass() === 'a_password';
                }),
                $userDataDto
            );

        $this->userService
            ->expects($this->once())
            ->method('updateLastLoginById')
            ->with($userDataDto->getId());

        $this->context
            ->expects($this->once())
            ->method('setUserData')
            ->with($userDataDto);

        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileService
            ->expects($this->once())
            ->method('getById')
            ->with($userDataDto->getUserProfileId())
            ->willReturn($userProfile);

        $this->context
            ->expects($this->once())
            ->method('setUserProfile')
            ->with($userProfile->hydrate(ProfileData::class));

        $this->language
            ->expects($this->once())
            ->method('setLanguage')
            ->with(true);

        $this->context
            ->expects($this->once())
            ->method('setAuthCompleted')
            ->with(true);

        $out = $this->login->doLogin($from);

        $this->assertEquals(LoginStatus::OK, $out->getStatus());
        $this->assertEquals($redirect, $out->getRedirect());
    }

    /**
     * @throws AuthException
     */
    #[DataProvider('loginInputProvider')]
    public function testDoLoginWithEmptyUserOrPass(string $user, string $pass)
    {
        $this->request
            ->expects($this->once())
            ->method('analyzeString')
            ->with('user')
            ->willReturn($user);

        $this->request
            ->expects($this->once())
            ->method('analyzeEncrypted')
            ->with('pass')
            ->willReturn($pass);

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Wrong login');

        $this->login->doLogin();
    }

    /**
     * @throws AuthException
     */
    public function testDoLoginWithNullUserData()
    {
        $userDataDto = new UserDataDto(UserDataGenerator::factory()->buildUserData());

        $this->request
            ->expects($this->once())
            ->method('analyzeString')
            ->with('user')
            ->willReturn('a_user');

        $this->request
            ->expects($this->once())
            ->method('analyzeEncrypted')
            ->with('pass')
            ->willReturn('a_password');

        $this->trackService
            ->expects($this->once())
            ->method('checkTracking')
            ->willReturn(false);

        $this->authProviderService
            ->expects($this->once())
            ->method('doAuth')
            ->with(
                self::callback(function (UserLoginDto $userLoginDto) {
                    return $userLoginDto->getLoginUser() === 'a_user'
                           && $userLoginDto->getLoginPass() === 'a_password';
                }),
                self::callback(function (array $callable) {
                    return $callable[0] instanceof Login
                           && $callable[1] === 'handleAuthResponse';
                })
            )
            ->willReturn(null);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Internal error');

        $this->login->doLogin();
    }

    /**
     * @throws AuthException
     */
    #[DataProvider('loginStatusDataProvider')]
    public function testDoLoginWithCheckUserFail(LoginStatus $loginStatus)
    {
        $userDataDto = new UserDataDto(UserDataGenerator::factory()->buildUserData());

        $this->request
            ->expects($this->once())
            ->method('analyzeString')
            ->with('user')
            ->willReturn('a_user');

        $this->request
            ->expects($this->once())
            ->method('analyzeEncrypted')
            ->with('pass')
            ->willReturn('a_password');

        $this->trackService
            ->expects($this->once())
            ->method('checkTracking')
            ->willReturn(false);

        $this->authProviderService
            ->expects($this->once())
            ->method('doAuth')
            ->with(
                self::callback(function (UserLoginDto $userLoginDto) {
                    return $userLoginDto->getLoginUser() === 'a_user'
                           && $userLoginDto->getLoginPass() === 'a_password';
                }),
                self::callback(function (array $callable) {
                    return $callable[0] instanceof Login
                           && $callable[1] === 'handleAuthResponse';
                })
            )
            ->willReturn($userDataDto);

        $this->loginUserService
            ->expects($this->once())
            ->method('checkUser')
            ->with($userDataDto)
            ->willReturn(new LoginResponseDto($loginStatus));

        $out = $this->login->doLogin();

        $this->assertEquals($loginStatus, $out->getStatus());
    }

    /**
     * @throws AuthException
     */
    public function testDoLoginWithServiceException()
    {
        $userDataDto = new UserDataDto(UserDataGenerator::factory()->buildUserData());

        $this->request
            ->expects($this->once())
            ->method('analyzeString')
            ->with('user')
            ->willReturn('a_user');

        $this->request
            ->expects($this->once())
            ->method('analyzeEncrypted')
            ->with('pass')
            ->willReturn('a_password');

        $this->trackService
            ->expects($this->once())
            ->method('checkTracking')
            ->willReturn(false);

        $this->authProviderService
            ->expects($this->once())
            ->method('doAuth')
            ->willThrowException(ServiceException::error('test'));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('test');

        $this->login->doLogin();
    }

    /**
     * @throws AuthException
     */
    public function testDoLoginWithException()
    {
        $userDataDto = new UserDataDto(UserDataGenerator::factory()->buildUserData());

        $this->request
            ->expects($this->once())
            ->method('analyzeString')
            ->with('user')
            ->willReturn('a_user');

        $this->request
            ->expects($this->once())
            ->method('analyzeEncrypted')
            ->with('pass')
            ->willReturn('a_password');

        $this->trackService
            ->expects($this->once())
            ->method('checkTracking')
            ->willReturn(false);

        $this->authProviderService
            ->expects($this->once())
            ->method('doAuth')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        $this->login->doLogin();
    }

    /**
     * @throws Exception
     */
    protected function buildContext(): Context
    {
        return $this->createMock(SessionContext::class);
    }

    /**
     * @throws Exception
     * @throws ContextException
     * @throws InvalidArgumentException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trackService = $this->createMock(TrackService::class);
        $this->trackService
            ->expects($this->once())
            ->method('buildTrackRequest')
            ->with(Login::class)
            ->willReturn(
                new TrackRequest(
                    self::$faker->unixTime(),
                    self::$faker->colorName(),
                    self::$faker->ipv4(),
                    self::$faker->randomNumber(2)
                )
            );

        $this->request = $this->createMock(RequestInterface::class);
        $this->authProviderService = $this->createMockForIntersectionOfInterfaces(
            [AuthProviderService::class, ProviderInterface::class]
        );
        $this->authProviderService
            ->expects($this->once())
            ->method('initialize');

        $this->language = $this->createMock(LanguageInterface::class);
        $this->userService = $this->createMock(UserService::class);
        $this->loginUserService = $this->createMock(LoginUserService::class);
        $this->loginMasterPassService = $this->createMock(LoginMasterPassService::class);
        $this->userProfileService = $this->createMock(UserProfileService::class);
        $this->loginAuthHandlerService = $this->createMock(LoginAuthHandlerService::class);

        $this->login = new Login(
            $this->application,
            $this->trackService,
            $this->request,
            $this->authProviderService,
            $this->language,
            $this->userService,
            $this->loginUserService,
            $this->loginMasterPassService,
            $this->userProfileService,
            $this->loginAuthHandlerService
        );
    }
}
