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

namespace SP\Tests\Core\Crypt;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SessionHandler;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Tests\UnitaryTestCase;

/**
 * Class CryptSessionHandlerTest
 */
#[Group('unitary')]
class CryptSessionHandlerTest extends UnitaryTestCase
{

    private MockObject|CryptInterface $crypt;
    private CryptSessionHandler       $cryptSessionHandler;
    private MockObject|SessionHandler $sessionHandler;
    private Key                       $key;

    public function testRead()
    {
        $this->sessionHandler
            ->expects($this->once())
            ->method('read')
            ->with('test')
            ->willReturn('session_data');

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->with('session_data', $this->key)
            ->willReturn('decrypted_session_data');

        $out = $this->cryptSessionHandler->read('test');

        $this->assertEquals('decrypted_session_data', $out);
    }

    public function testReadWithNodata()
    {
        $this->sessionHandler
            ->expects($this->once())
            ->method('read')
            ->with('test')
            ->willReturn(false);

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $out = $this->cryptSessionHandler->read('test');

        $this->assertEmpty($out);
    }

    public function testReadWithException()
    {
        $this->sessionHandler
            ->expects($this->once())
            ->method('read')
            ->with('test')
            ->willReturn('session_data');

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->willThrowException(CryptException::error('test'));

        $out = $this->cryptSessionHandler->read('test');

        $this->assertEquals('session_data', $out);
    }

    public function testWrite()
    {
        $data = serialize(['a' => 'testA']);

        $this->sessionHandler
            ->expects($this->once())
            ->method('write')
            ->with('test', 'encrypted_session_data')
            ->willReturn(true);

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with($data, $this->key)
            ->willReturn('encrypted_session_data');

        $this->assertTrue($this->cryptSessionHandler->write('test', $data));
    }

    public function testWriteWithException()
    {
        $data = serialize(['a' => 'testA']);

        $this->sessionHandler
            ->expects($this->once())
            ->method('write')
            ->with('test', $data)
            ->willReturn(true);

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->willThrowException(CryptException::error('test'));

        $this->assertTrue($this->cryptSessionHandler->write('test', $data));
    }

    public function testClose()
    {
        $this->sessionHandler
            ->expects($this->once())
            ->method('close');

        $this->cryptSessionHandler->close();
    }

    public function testDestroy()
    {
        $this->sessionHandler
            ->expects($this->once())
            ->method('destroy')
            ->with('test');

        $this->cryptSessionHandler->destroy('test');
    }

    public function testGc()
    {
        $this->sessionHandler
            ->expects($this->once())
            ->method('gc')
            ->with(1000);

        $this->cryptSessionHandler->gc(1000);
    }

    public function testOpen()
    {
        $this->sessionHandler
            ->expects($this->once())
            ->method('open')
            ->with('a_path', 'test');

        $this->cryptSessionHandler->open('a_path', 'test');
    }

    /**
     * @throws ContextException
     * @throws Exception
     * @throws EnvironmentIsBrokenException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->key = Key::createNewRandomKey();
        $this->crypt = $this->createMock(CryptInterface::class);
        $this->sessionHandler = $this->createMock(SessionHandler::class);

        $this->cryptSessionHandler = new CryptSessionHandler($this->key, $this->crypt, $this->sessionHandler);
    }
}
