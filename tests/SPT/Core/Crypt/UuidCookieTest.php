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

use Klein\DataCollection\DataCollection;
use Klein\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\UuidCookie;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Http\RequestInterface;
use SPT\UnitaryTestCase;

/**
 * Class UuidCookieTest
 *
 */
#[Group('unitary')]
class UuidCookieTest extends UnitaryTestCase
{

    private RequestInterface|MockObject    $requestInterface;
    private UriContextInterface|MockObject $uriContext;


    /**
     * @throws Exception
     */
    public function testLoad()
    {
        $key = self::$faker->sha1;
        $message = base64_encode('test');
        $data = sprintf('%s;%s', Hash::signMessage($message, $key), $message);

        $cookies = $this->createMock(DataCollection::class);
        $cookies->expects(self::once())
                ->method('get')
                ->with('SYSPASS_UUID', false)
                ->willReturn($data);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
                ->method('cookies')
                ->willReturn($cookies);

        $this->requestInterface
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $cookie = UuidCookie::factory($this->requestInterface, $this->uriContext);

        self::assertEquals('test', $cookie->load($key));
    }

    /**
     * @throws Exception
     */
    public function testLoadWithNoData()
    {
        $key = self::$faker->sha1;

        $cookies = $this->createMock(DataCollection::class);
        $cookies->expects(self::once())
                ->method('get')
                ->with('SYSPASS_UUID', false)
                ->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
                ->method('cookies')
                ->willReturn($cookies);

        $this->requestInterface
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $cookie = UuidCookie::factory($this->requestInterface, $this->uriContext);

        self::assertFalse($cookie->load($key));
    }

    /**
     * @throws Exception
     */
    public function testLoadWithInvalidData()
    {
        $key = self::$faker->sha1;
        $data = self::$faker->text;

        $cookies = $this->createMock(DataCollection::class);
        $cookies->expects(self::once())
                ->method('get')
                ->with('SYSPASS_UUID', false)
                ->willReturn($data);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
                ->method('cookies')
                ->willReturn($cookies);

        $this->requestInterface
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $cookie = UuidCookie::factory($this->requestInterface, $this->uriContext);

        self::assertFalse($cookie->load($key));
    }

    /**
     * @throws Exception
     */
    public function testLoadWithInvalidSignature()
    {
        $key = self::$faker->sha1;
        $data = sprintf('%s;%s', Hash::signMessage(base64_encode('invalid'), $key), base64_encode('test'));

        $cookies = $this->createMock(DataCollection::class);
        $cookies->expects(self::once())
                ->method('get')
                ->with('SYSPASS_UUID', false)
                ->willReturn($data);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
                ->method('cookies')
                ->willReturn($cookies);

        $this->requestInterface
            ->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $cookie = UuidCookie::factory($this->requestInterface, $this->uriContext);

        self::assertFalse($cookie->load($key));
    }

    public function testCreate()
    {
        $uuidCookie = UuidCookie::factory($this->requestInterface, $this->uriContext);

        $key = self::$faker->sha1;
        $cookie = $uuidCookie->create($key);

        self::assertNotEmpty($cookie);
    }

    public function testSign()
    {
        $key = self::$faker->sha1;
        $uuidCookie = UuidCookie::factory($this->requestInterface, $this->uriContext);
        $cookieData = $uuidCookie->sign('test', $key);
        $out = $uuidCookie->getCookieData($cookieData, $key);

        self::assertEquals('test', $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestInterface = $this->createMock(RequestInterface::class);
        $this->uriContext = $this->createMock(UriContextInterface::class);
    }
}
