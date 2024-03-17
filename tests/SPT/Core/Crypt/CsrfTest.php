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

namespace SPT\Core\Crypt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Crypt\Csrf;
use SP\Core\Crypt\Hash;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Context\SessionContextInterface;
use SP\Domain\Http\Method;
use SP\Domain\Http\RequestInterface;
use SPT\UnitaryTestCase;

/**
 * Class CsrfTest
 *
 */
#[Group('unitary')]
class CsrfTest extends UnitaryTestCase
{

    private SessionContextInterface|MockObject $sessionContext;
    private RequestInterface|MockObject        $requestInterface;
    private ConfigDataInterface|MockObject     $configData;
    private Csrf                               $csrf;

    public static function httpMethodDataProvider(): array
    {
        return [
            [Method::POST, 'test'],
            [Method::GET, 'XMLHttpRequest']
        ];
    }

    public function testInitialize()
    {
        $this->sessionContext
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->sessionContext
            ->expects(self::exactly(2))
            ->method('getCSRF')
            ->willReturn(null);

        $key = $this->checkGetKey();

        $this->sessionContext
            ->expects(self::once())
            ->method('setCSRF')
            ->with($key);

        $this->csrf->initialize();
    }

    private function checkGetKey(): string
    {
        $salt = self::$faker->sha1;
        $userAgent = self::$faker->userAgent;

        $this->requestInterface
            ->expects(self::once())
            ->method('getHeader')
            ->with('User-Agent')
            ->willReturn($userAgent);

        $ipv4 = self::$faker->ipv4;

        $this->requestInterface
            ->expects(self::once())
            ->method('getClientAddress')
            ->willReturn($ipv4);

        $this->configData
            ->expects(self::once())
            ->method('getPasswordSalt')
            ->willReturn($salt);

        return Hash::signMessage(sha1($userAgent . $ipv4), $salt);
    }

    /**
     * @return void
     */
    #[DataProvider('httpMethodDataProvider')]
    public function testCheckWithValidToken(Method $method, string $header)
    {
        $salt = self::$faker->sha1;
        $userAgent = self::$faker->userAgent;
        $ipv4 = self::$faker->ipv4;
        $key = Hash::signMessage(sha1($userAgent . $ipv4), $salt);

        $this->requestInterface
            ->expects(self::once())
            ->method('getMethod')
            ->willReturn($method);

        $this->requestInterface
            ->expects(self::exactly(3))
            ->method('getHeader')
            ->with(...self::withConsecutive(['X-Requested-With'], ['X-CSRF'], ['User-Agent']))
            ->willReturn($header, $key, $userAgent);

        $this->requestInterface
            ->expects(self::once())
            ->method('getClientAddress')
            ->willReturn($ipv4);

        $this->configData
            ->expects(self::once())
            ->method('getPasswordSalt')
            ->willReturn($salt);

        $this->sessionContext
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->sessionContext
            ->expects(self::once())
            ->method('getCSRF')
            ->willReturn(self::$faker->sha1);

        self::assertTrue($this->csrf->check());
    }


    /**
     * @return void
     */
    #[DataProvider('httpMethodDataProvider')]
    public function testCheckWithInvalidToken(Method $method, string $header)
    {
        $this->requestInterface
            ->expects(self::once())
            ->method('getMethod')
            ->willReturn($method);

        $this->requestInterface
            ->expects(self::exactly(3))
            ->method('getHeader')
            ->with(...self::withConsecutive(['X-Requested-With'], ['X-CSRF'], ['User-Agent']))
            ->willReturn($header, self::$faker->sha1, self::$faker->userAgent);

        $this->requestInterface
            ->expects(self::once())
            ->method('getClientAddress')
            ->willReturn(self::$faker->ipv4);

        $this->configData
            ->expects(self::once())
            ->method('getPasswordSalt')
            ->willReturn(self::$faker->sha1);

        $this->sessionContext
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->sessionContext
            ->expects(self::once())
            ->method('getCSRF')
            ->willReturn(self::$faker->sha1);

        self::assertFalse($this->csrf->check());
    }

    /**
     * @return void
     */
    #[DataProvider('httpMethodDataProvider')]
    public function testCheckWithNoToken(Method $method, string $header)
    {
        $this->requestInterface
            ->expects(self::once())
            ->method('getMethod')
            ->willReturn($method);

        $this->requestInterface
            ->expects(self::exactly(2))
            ->method('getHeader')
            ->with(...self::withConsecutive(['X-Requested-With'], ['X-CSRF']))
            ->willReturn($header, '');

        $this->sessionContext
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->sessionContext
            ->expects(self::once())
            ->method('getCSRF')
            ->willReturn(self::$faker->sha1);

        self::assertFalse($this->csrf->check());
    }

    /**
     * @return void
     */
    public function testCheckWithNoLogin()
    {
        $this->requestInterface
            ->expects(self::once())
            ->method('getMethod')
            ->willReturn(Method::GET);

        $this->requestInterface
            ->expects(self::once())
            ->method('getHeader')
            ->with('X-Requested-With')
            ->willReturn('test');

        $this->sessionContext
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(false);

        self::assertTrue($this->csrf->check());
    }

    /**
     * @return void
     */
    public function testCheckWithNoCsrf()
    {
        $this->requestInterface
            ->expects(self::once())
            ->method('getMethod')
            ->willReturn(Method::GET);

        $this->requestInterface
            ->expects(self::once())
            ->method('getHeader')
            ->with('X-Requested-With')
            ->willReturn('test');

        $this->sessionContext
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->sessionContext
            ->expects(self::once())
            ->method('getCSRF')
            ->willReturn(null);

        self::assertTrue($this->csrf->check());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionContext = $this->createMock(SessionContextInterface::class);
        $this->requestInterface = $this->createMock(RequestInterface::class);
        $this->configData = $this->createMock(ConfigDataInterface::class);

        $this->csrf = new Csrf($this->sessionContext, $this->requestInterface, $this->configData);
    }
}
