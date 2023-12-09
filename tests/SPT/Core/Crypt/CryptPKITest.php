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

use phpseclib\Crypt\RSA;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Crypt\CryptPKI;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;
use SPT\UnitaryTestCase;

use function PHPUnit\Framework\once;

/**
 * Class CryptPKITest
 *
 * @group unitary
 */
class CryptPKITest extends UnitaryTestCase
{
    private CryptPKI                        $cryptPki;
    private RSA|MockObject                  $rsa;
    private FileHandlerInterface|MockObject $privateKey;
    private FileHandlerInterface|MockObject $publicKey;

    /**
     * @throws FileException
     */
    public function testDecryptRSA()
    {
        $data = self::$faker->sha1;
        $privateKey = self::$faker->sha1;

        $this->privateKey->expects(once())->method('checkFileExists')->willReturnSelf();
        $this->privateKey->expects(once())->method('readToString')->willReturn($privateKey);
        $this->rsa->expects(once())->method('setEncryptionMode')->with(RSA::ENCRYPTION_PKCS1);
        $this->rsa->expects(once())->method('loadKey')->with($privateKey, RSA::PRIVATE_FORMAT_PKCS1);
        $this->rsa->expects(once())->method('decrypt')->with('test')->willReturn($data);

        $out = $this->cryptPki->decryptRSA('test');

        $this->assertEquals($data, $out);
    }

    /**
     * @throws FileException
     */
    public function testGetPublicKey()
    {
        $this->publicKey->expects(once())->method('checkFileExists')->willReturnSelf();
        $this->publicKey->expects(once())->method('readToString')->willReturn('test');

        $out = $this->cryptPki->getPublicKey();

        $this->assertEquals('test', $out);
    }

    /**
     * @throws SPException
     */
    public function testCreateKeys()
    {
        $this->publicKey->expects(once())->method('checkFileExists')->willReturnSelf();
        $this->privateKey->expects(once())
            ->method('checkFileExists')
            ->willThrowException(new FileException('test'));

        $keys = ['publickey' => self::$faker->sha1, 'privatekey' => self::$faker->sha1];

        $this->rsa->expects(once())->method('createKey')->with(CryptPKI::KEY_SIZE)->willReturn($keys);

        $this->privateKey->expects(once())->method('save')->with($keys['privatekey'])->willReturnSelf();
        $this->privateKey->expects(once())->method('chmod')->with(0600);
        $this->publicKey->expects(once())->method('save')->with($keys['publickey']);

        new CryptPKI($this->rsa, $this->publicKey, $this->privateKey);
    }

    public function testGetMaxDataSize()
    {
        $this->assertEquals(117, CryptPKI::getMaxDataSize());
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws SPException
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->rsa = $this->createMock(RSA::class);
        $this->privateKey = $this->createMock(FileHandlerInterface::class);
        $this->publicKey = $this->createMock(FileHandlerInterface::class);

        $this->cryptPki = new CryptPKI($this->rsa, $this->publicKey, $this->privateKey);
    }
}
