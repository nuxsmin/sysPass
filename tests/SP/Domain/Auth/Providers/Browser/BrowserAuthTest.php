<?php
declare(strict_types=1);
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

namespace SP\Tests\Domain\Auth\Providers\Browser;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Providers\Browser\BrowserAuth;
use SP\Domain\Auth\Providers\Browser\BrowserAuthData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Tests\UnitaryTestCase;

/**
 * Class BrowserAuthTest
 *
 */
#[Group('unitary')]
class BrowserAuthTest extends UnitaryTestCase
{

    private RequestService|MockObject $request;
    private BrowserAuth               $browserAuth;
    private ConfigDataInterface|MockObject $configData;

    public function testGetServerAuthUser()
    {
        $this->request
            ->expects(self::exactly(2))
            ->method('getServer')
            ->with(...$this->withConsecutive(['PHP_AUTH_USER'], ['REMOTE_USER']))
            ->willReturn('');

        self::assertNull($this->browserAuth->getServerAuthUser());
    }

    public function testGetServerAuthUserWithAuthUser()
    {
        $this->request
            ->expects(self::exactly(1))
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn('test');

        self::assertEquals('test', $this->browserAuth->getServerAuthUser());
    }

    public function testGetServerAuthUserWithRemoteUser()
    {
        $this->request
            ->expects(self::exactly(2))
            ->method('getServer')
            ->with(...$this->withConsecutive(['PHP_AUTH_USER'], ['REMOTE_USER']))
            ->willReturn('', 'test');

        self::assertEquals('test', $this->browserAuth->getServerAuthUser());
    }

    public function testAuthenticate()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        $this->configData
            ->expects(self::once())
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(true);

        $this->configData
            ->expects(self::once())
            ->method('getAuthBasicDomain')
            ->willReturn('localhost');

        $this->request
            ->expects(self::once())
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn(sprintf('%s@localhost', $user));

        $out = $this->browserAuth->authenticate($userLoginData);

        self::assertInstanceOf(BrowserAuthData::class, $out);
        self::assertTrue($out->isOk());
    }

    public function testAuthenticateWithAuthBasic()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $this->configData
            ->expects(self::exactly(2))
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(true);

        $this->request
            ->expects(self::exactly(2))
            ->method('getServer')
            ->with(...$this->withConsecutive(['PHP_AUTH_USER'], ['PHP_AUTH_PW']))
            ->willReturn($user, $pass);

        $out = $this->browserAuth->authenticate(new UserLoginDto());

        self::assertInstanceOf(BrowserAuthData::class, $out);
        self::assertTrue($out->isOk());
    }

    public function testAuthenticateWithAuthBasicNoUser()
    {
        $pass = self::$faker->password;

        $this->configData
            ->expects(self::exactly(2))
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(true);

        $this->request
            ->expects(self::exactly(3))
            ->method('getServer')
            ->with(...$this->withConsecutive(['PHP_AUTH_USER'], ['REMOTE_USER'], ['PHP_AUTH_PW']))
            ->willReturn('', '', '', $pass);

        $out = $this->browserAuth->authenticate(new UserLoginDto());

        self::assertInstanceOf(BrowserAuthData::class, $out);
        self::assertFalse($out->isOk());
    }

    public function testAuthenticateWithAuthBasicNoPassword()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::exactly(2))
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(true);

        $this->request
            ->expects(self::exactly(2))
            ->method('getServer')
            ->with(...$this->withConsecutive(['PHP_AUTH_USER'], ['PHP_AUTH_PW']))
            ->willReturn($user, '');

        $out = $this->browserAuth->authenticate(new UserLoginDto());

        self::assertInstanceOf(BrowserAuthData::class, $out);
        self::assertFalse($out->isOk());
    }

    public function testAuthenticateWithServerAuth()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::exactly(2))
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(false);

        $this->request
            ->expects(self::once())
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn($user);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);

        $out = $this->browserAuth->authenticate($userLoginData);

        self::assertInstanceOf(BrowserAuthData::class, $out);
        self::assertTrue($out->isOk());
    }

    public function testAuthenticateWithServerAuthFail()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::exactly(2))
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(false);

        $this->request
            ->expects(self::once())
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn($user);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser(self::$faker->userName);

        $out = $this->browserAuth->authenticate($userLoginData);

        self::assertInstanceOf(BrowserAuthData::class, $out);
        self::assertFalse($out->isOk());
    }

    public function testIsAuthGrantedTrue()
    {
        $this->configData
            ->expects(self::once())
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(true);

        self::assertTrue($this->browserAuth->isAuthGranted());
    }

    public function testIsAuthGrantedFalse()
    {
        $this->configData
            ->expects(self::once())
            ->method('isAuthBasicAutoLoginEnabled')
            ->willReturn(false);

        self::assertFalse($this->browserAuth->isAuthGranted());
    }

    public function testCheckServerAuthUserWithoutServerAuth()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::once())
            ->method('getAuthBasicDomain')
            ->willReturn('localhost');

        $this->request
            ->expects(self::exactly(2))
            ->method('getServer')
            ->with(...$this->withConsecutive(['PHP_AUTH_USER'], ['REMOTE_USER']))
            ->willReturn('', '');

        self::assertNull($this->browserAuth->checkServerAuthUser($user));
    }

    public function testCheckServerAuthUserWithDomain()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::once())
            ->method('getAuthBasicDomain')
            ->willReturn('localhost');

        $this->request
            ->expects(self::once())
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn(sprintf('%s@localhost', $user));

        self::assertTrue($this->browserAuth->checkServerAuthUser($user));
    }

    public function testCheckServerAuthUserWithDomainAndNoUserDomain()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::once())
            ->method('getAuthBasicDomain')
            ->willReturn('localhost');

        $this->request
            ->expects(self::once())
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn($user);

        self::assertTrue($this->browserAuth->checkServerAuthUser($user));
    }

    public function testCheckServerAuthUserWithoutDomain()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::once())
            ->method('getAuthBasicDomain');

        $this->request
            ->expects(self::once())
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn($user);

        self::assertTrue($this->browserAuth->checkServerAuthUser($user));
    }

    public function testCheckServerAuthUserMismatchDomain()
    {
        $user = self::$faker->userName;

        $this->configData
            ->expects(self::once())
            ->method('getAuthBasicDomain')
            ->willReturn(self::$faker->domainName);

        $this->request
            ->expects(self::once())
            ->method('getServer')
            ->with('PHP_AUTH_USER')
            ->willReturn(sprintf('%s@localhost', $user));

        self::assertFalse($this->browserAuth->checkServerAuthUser($user));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configData = $this->createMock(ConfigDataInterface::class);
        $this->request = $this->createMock(RequestService::class);

        $this->browserAuth = new BrowserAuth($this->configData, $this->request);
    }

}
