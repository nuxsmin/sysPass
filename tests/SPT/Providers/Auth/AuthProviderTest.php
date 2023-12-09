<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Providers\Auth;

use PHPUnit\Framework\MockObject\Exception;
use SP\DataModel\UserLoginData;
use SP\Domain\Auth\Services\AuthException;
use SP\Providers\Auth\AuthInterface;
use SP\Providers\Auth\AuthProvider;
use SP\Providers\Auth\AuthTypeEnum;
use SP\Providers\Auth\Browser\BrowserAuthData;
use SPT\UnitaryTestCase;

/**
 * Class AuthProviderTest
 *
 * @group unitary
 */
class AuthProviderTest extends UnitaryTestCase
{

    private AuthProvider $authProvider;

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function testRegisterAuthFail()
    {
        $auth1 = $this->createMock(AuthInterface::class);

        $this->authProvider->registerAuth($auth1, AuthTypeEnum::Ldap);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Authentication already initialized');

        $this->authProvider->registerAuth($auth1, AuthTypeEnum::Ldap);
    }

    /**
     * @throws AuthException
     * @throws Exception
     */
    public function testDoAuth()
    {
        $userLoginData = new UserLoginData();
        $userLoginData->setLoginUser(self::$faker->userName);
        $userLoginData->setLoginPass(self::$faker->password);

        $browserAuthData = new BrowserAuthData(false);
        $browserAuthData->setName(self::$faker->name);
        $browserAuthData->setEmail(self::$faker->email);
        $browserAuthData->setStatusCode(0);
        $browserAuthData->success();

        $auth = $this->createMock(AuthInterface::class);
        $auth->expects(self::once())
             ->method('authenticate')
             ->with($userLoginData)
             ->willReturn($browserAuthData);

        $this->authProvider->registerAuth($auth, AuthTypeEnum::Ldap);

        $out = $this->authProvider->doAuth($userLoginData);

        self::assertCount(1, $out);
        self::assertEquals(AuthTypeEnum::Ldap->value, $out[0]->getAuthName());
        self::assertEquals($browserAuthData, $out[0]->getData());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->authProvider = new AuthProvider($this->application);
    }


}
