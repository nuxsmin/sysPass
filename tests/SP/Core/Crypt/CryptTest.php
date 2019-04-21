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

namespace SP\Tests\Core\Crypt;

use Defuse\Crypto\Exception\CryptoException;
use PHPUnit\Framework\TestCase;
use SP\Core\Crypt\Crypt;

/**
 * Class CryptTest
 *
 * Tests unitarios para comprobar el funcionamiento de la clase SP\Core\Crypt\Crypt
 *
 * @package SP\Tests
 */
class CryptTest extends TestCase
{
    const PASSWORD = 'test_password';

    /**
     * Comprobar la generación de una llave de cifrado
     *
     * @throws CryptoException
     */
    public function testMakeSecuredKey()
    {
        $this->assertTrue(true);

        return Crypt::makeSecuredKey(self::PASSWORD);
    }

    /**
     * Comprobar el desbloqueo de una llave de cifrado
     *
     * @depends testMakeSecuredKey
     *
     * @param string $key LLave de cifrado
     *
     * @throws CryptoException
     */
    public function testUnlockSecuredKey($key)
    {
        $this->assertTrue(true);

        Crypt::unlockSecuredKey($key, self::PASSWORD);
    }

    /**
     * Comprobar el desbloqueo de una llave de cifrado
     *
     * @depends testMakeSecuredKey
     *
     * @param string $key LLave de cifrado
     *
     * @throws CryptoException
     */
    public function testUnlockSecuredKeyWithWrongPassword($key)
    {
        $this->expectException(CryptoException::class);

        Crypt::unlockSecuredKey($key, 'test');
    }

    /**
     * Comprobar la encriptación y desencriptado de datos
     *
     * @depends testMakeSecuredKey
     *
     * @param string $key LLave de cifrado
     *
     * @throws CryptoException
     */
    public function testEncryptAndDecrypt($key)
    {
        $data = Crypt::encrypt('prueba', $key, self::PASSWORD);

        $this->assertSame('prueba', Crypt::decrypt($data, $key, self::PASSWORD));
    }

    /**
     * Comprobar la encriptación y desencriptado de datos
     *
     * @depends testMakeSecuredKey
     *
     * @param string $key LLave de cifrado
     *
     * @throws CryptoException
     */
    public function testEncryptAndDecryptWithDifferentPassword($key)
    {
        $data = Crypt::encrypt('prueba', $key, self::PASSWORD);

        $this->expectException(CryptoException::class);

        $this->assertSame('prueba', Crypt::decrypt($data, $key, 'test'));
    }
}
