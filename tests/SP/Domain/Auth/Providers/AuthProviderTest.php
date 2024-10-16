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

namespace SP\Tests\Domain\Auth\Providers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Providers\AuthProvider;
use SP\Domain\Auth\Providers\AuthResult;
use SP\Domain\Auth\Providers\AuthService;
use SP\Domain\Auth\Providers\AuthType;
use SP\Domain\Auth\Providers\Browser\BrowserAuthData;
use SP\Domain\Auth\Providers\Database\DatabaseAuthData;
use SP\Domain\Auth\Providers\Ldap\LdapAuthData;
use SP\Domain\Auth\Services\AuthException;
use SP\Tests\UnitaryTestCase;

/**
 * Class AuthProviderTest
 *
 */
#[Group('unitary')]
class AuthProviderTest extends UnitaryTestCase
{

    private AuthProvider $authProvider;

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function testRegisterAuthFail()
    {
        $auth1 = $this->createMock(AuthService::class);

        $this->authProvider->registerAuth($auth1, AuthType::Ldap);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Authentication already initialized');

        $this->authProvider->registerAuth($auth1, AuthType::Ldap);
    }

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function testDoAuth()
    {
        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser(self::$faker->userName);
        $userLoginData->setLoginPass(self::$faker->password);

        $browserAuthData = new BrowserAuthData(false);
        $browserAuthData->setName(self::$faker->name);
        $browserAuthData->setEmail(self::$faker->email);
        $browserAuthData->setStatusCode(0);
        $browserAuthData->success();

        $ldapAuthData = new LdapAuthData(true);
        $ldapAuthData->setName(self::$faker->name);
        $ldapAuthData->setEmail(self::$faker->email);
        $ldapAuthData->setStatusCode(1);
        $ldapAuthData->success();

        $databaseAuthData = new DatabaseAuthData(true);
        $databaseAuthData->setName(self::$faker->name);
        $databaseAuthData->setEmail(self::$faker->email);
        $databaseAuthData->setStatusCode(2);
        $databaseAuthData->success();

        $authBrowser = $this->createMock(AuthService::class);
        $authBrowser->expects(self::once())
                    ->method('authenticate')
                    ->with($userLoginData)
                    ->willReturn($browserAuthData);

        $authLdap = $this->createMock(AuthService::class);
        $authLdap->expects(self::once())
                 ->method('authenticate')
                 ->with($userLoginData)
                 ->willReturn($ldapAuthData);

        $authDatabase = $this->createMock(AuthService::class);
        $authDatabase->expects(self::never())
                     ->method('authenticate');

        $this->authProvider->registerAuth($authBrowser, AuthType::Browser);
        $this->authProvider->registerAuth($authLdap, AuthType::Ldap);
        $this->authProvider->registerAuth($authDatabase, AuthType::Database);

        $callback = static function (AuthResult $authResult) {
            $authData = $authResult->getAuthData();
            return $authData->isAuthoritative() && !$authData->isOk();
        };

        $this->authProvider->doAuth($userLoginData, $callback);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->authProvider = new AuthProvider($this->application);
    }
}
