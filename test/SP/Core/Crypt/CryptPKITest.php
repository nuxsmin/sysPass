<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\SP\Core\Crypt;

use phpseclib\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use SP\Core\Crypt\CryptPKI;
use SP\Util\Util;

/**
 * Class CryptPKITest
 *
 * @package SP\Tests\SP\Core\Crypt
 */
class CryptPKITest extends TestCase
{
    /**
     * @var CryptPKI
     */
    private $cryptPki;

    /**
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function testDecryptRSA()
    {
        $random = Util::generateRandomBytes();

        $data = $this->cryptPki->encryptRSA($random);

        $this->assertNotEmpty($data);

        $this->assertEquals($random, $this->cryptPki->decryptRSA($data));

        $this->assertFalse($this->cryptPki->decryptRSA('test123'));
    }

    /**
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function testGetPublicKey()
    {
        $key = $this->cryptPki->getPublicKey();

        $this->assertNotEmpty($key);

        $this->assertRegExp('/^-----BEGIN PUBLIC KEY-----.*/', $key);
    }

    /**
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function testGetPrivateKey()
    {
        $key = $this->cryptPki->getPrivateKey();

        $this->assertNotEmpty($key);

        $this->assertRegExp('/^-----BEGIN RSA PRIVATE KEY-----.*/', $key);
    }

    /**
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function testEncryptRSA()
    {
        $random = Util::generateRandomBytes();

        $data = $this->cryptPki->encryptRSA($random);

        $this->assertNotEmpty($data);

        $this->assertEquals($random, $this->cryptPki->decryptRSA($data));

        // Encrypt a long message
        $random = Util::generateRandomBytes(128);

        $data = $this->cryptPki->encryptRSA($random);

        $this->assertNotEmpty($data);

        $this->assertEquals($random, $this->cryptPki->decryptRSA($data));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreateKeys()
    {
        $this->cryptPki->createKeys();

        $this->assertFileExists($this->cryptPki->getPublicKeyFile());
        $this->assertFileExists($this->cryptPki->getPrivateKeyFile());
    }

    /**
     * testCheckKeys
     */
    public function testCheckKeys()
    {
        $this->assertTrue($this->cryptPki->checkKeys());
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function setUp()
    {
        $this->cryptPki = new CryptPKI(new RSA());
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        unlink($this->cryptPki->getPublicKeyFile());
        unlink($this->cryptPki->getPrivateKeyFile());
    }


}
