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

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Crypt\CryptPKI;
use SP\Domain\Core\Exceptions\FileException;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Tests\Stubs\InMemoryFileHandler;
use SP\Tests\UnitaryTestCase;

use function PHPUnit\Framework\once;

/**
 * Class CryptPKITest
 */
#[Group('unitary')]
class CryptPKITest extends UnitaryTestCase
{
    private CryptPKI           $cryptPki;
    private InMemoryFileHandler $privateKey;
    private InMemoryFileHandler $publicKey;

    /**
     * @throws FileException
     */
    #[Test]
    public function testDecryptRSA(): void
    {
        $plainText = 'test plain text';

        $this->cryptPki->createKeys();

        $publicKey = PublicKeyLoader::load($this->cryptPki->getPublicKey());

        $this->assertInstanceOf(RSA\PublicKey::class, $publicKey);

        $encrypted = $publicKey->withPadding(RSA::ENCRYPTION_PKCS1)->encrypt($plainText);

        $this->assertSame($plainText, $this->cryptPki->decryptRSA($encrypted));
    }

    /**
     * @throws FileException
     */
    #[Test]
    public function testDecryptRSAWithInvalidInput(): void
    {
        $this->cryptPki->createKeys();

        // Ubuntu 24.04's OpenSSL 3.0.13 backports PKCS#1 v1.5 implicit rejection
        // (random plaintext instead of a padding error) while phpseclib only
        // detects it from OpenSSL >=3.2, so invalid input would not deterministically
        // return null. Force the pure-PHP engine for these cases (see #2113).
        RSA::forceEngine('PHP');

        try {
            $this->assertNull($this->cryptPki->decryptRSA('invalid'));
            $this->assertNull(
                $this->cryptPki->decryptRSA(str_repeat("\0", CryptPKI::KEY_SIZE / 8))
            );
            $this->assertNull(
                $this->cryptPki->decryptRSA(str_repeat("\xff", CryptPKI::KEY_SIZE / 8))
            );
        } finally {
            RSA::forceEngine(null);
        }
    }

    /**
     * @throws FileException
     */
    #[Test]
    public function testDecryptRSAWithNonBlockLengthInput(): void
    {
        $this->cryptPki->createKeys();

        $this->assertNull($this->cryptPki->decryptRSA((string)base64_decode('new-password')));
        $this->assertNull($this->cryptPki->decryptRSA(''));
        $this->assertNull(
            $this->cryptPki->decryptRSA(str_repeat('a', CryptPKI::KEY_SIZE / 8 - 1))
        );
        $this->assertNull(
            $this->cryptPki->decryptRSA(str_repeat('a', CryptPKI::KEY_SIZE / 8 + 1))
        );
    }

    /**
     * @throws FileException
     */
    #[Test]
    public function testGetPublicKey(): void
    {
        $this->publicKey->save('test');

        $this->assertSame('test', $this->cryptPki->getPublicKey());
    }

    /**
     * @throws FileException
     */
    #[Test]
    public function testCreateKeys(): void
    {
        $this->cryptPki->createKeys();

        $this->assertStringStartsWith('-----BEGIN PUBLIC KEY-----', $this->cryptPki->getPublicKey());
        $this->assertStringStartsWith(
            '-----BEGIN RSA PRIVATE KEY-----',
            $this->privateKey->readToString()
        );
    }

    /**
     * @throws FileException
     */
    #[Test]
    public function testCreateKeysProducesCompatiblePair(): void
    {
        $this->cryptPki->createKeys();

        $publicKey = PublicKeyLoader::load($this->cryptPki->getPublicKey());
        $privateKey = PublicKeyLoader::loadPrivateKey($this->privateKey->readToString());

        $this->assertInstanceOf(RSA\PublicKey::class, $publicKey);
        $this->assertInstanceOf(RSA\PrivateKey::class, $privateKey);
        $this->assertSame(
            $privateKey->getPublicKey()->toString('PKCS8'),
            $publicKey->toString('PKCS8')
        );
    }

    /**
     * @throws FileException
     */
    #[Test]
    public function testCreateKeysWhenFilesAreMissing(): void
    {
        $publicKey = $this->createMock(FileHandlerInterface::class);
        $privateKey = $this->createMock(FileHandlerInterface::class);

        $publicKey->expects(once())
                  ->method('getFileSize')
                  ->with(true)
                  ->willThrowException(new FileException('test'));

        $publicKey->expects(once())
                  ->method('save')
                  ->with($this->stringContains('-----BEGIN PUBLIC KEY-----'));

        $privateKey->expects(once())
                   ->method('save')
                   ->with($this->stringContains('-----BEGIN RSA PRIVATE KEY-----'))
                   ->willReturnSelf();

        $privateKey->expects(once())
                   ->method('chmod')
                   ->with(0600);

        new CryptPKI($publicKey, $privateKey);
    }

    #[Test]
    public function testGetMaxDataSize(): void
    {
        $this->assertEquals(117, CryptPKI::getMaxDataSize());
    }

    protected function setUp(): void
    {
        $this->privateKey = new InMemoryFileHandler('key.pem');
        $this->publicKey = new InMemoryFileHandler('pubkey.pem');

        $this->cryptPki = new CryptPKI($this->publicKey, $this->privateKey);
    }
}
