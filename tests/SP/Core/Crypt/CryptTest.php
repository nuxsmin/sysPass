<?php
declare(strict_types=1);
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

namespace SP\Tests\Core\Crypt;

use PHPUnit\Framework\Attributes\Group;
use SP\Core\Crypt\Crypt;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Tests\UnitaryTestCase;

/**
 * Class CryptTest
 */
#[Group('unitary')]
class CryptTest extends UnitaryTestCase
{
    /**
     * Comprobar la generación de una llave de cifrado
     *
     * @throws CryptException
     */
    public function testMakeSecuredKey()
    {
        (new Crypt())->makeSecuredKey(self::$faker->password);

        $this->assertTrue(true);
    }

    /**
     * Comprobar la generación de una llave de cifrado
     *
     * @throws CryptException
     */
    public function testMakeSecuredKeyNoAscii()
    {
        (new Crypt())->makeSecuredKey(self::$faker->password, false);

        $this->assertTrue(true);
    }

    /**
     * Comprobar la encriptación y desencriptado de datos
     *
     * @throws CryptException
     */
    public function testEncryptAndDecrypt()
    {
        $crypt = new Crypt();

        $password = self::$faker->password;

        $key = $crypt->makeSecuredKey($password);

        $data = self::$faker->text;

        $out = $crypt->encrypt($data, $key, $password);

        $this->assertSame($data, $crypt->decrypt($out, $key, $password));
    }

    /**
     * Comprobar la encriptación y desencriptado de datos
     *
     * @throws CryptException
     */
    public function testEncryptAndDecryptWithDifferentPassword()
    {
        $crypt = new Crypt();

        $password = self::$faker->password;

        $key = $crypt->makeSecuredKey($password);

        $data = $crypt->encrypt('prueba', $key, $password);

        $this->expectException(CryptException::class);

        $crypt->decrypt($data, $key, 'test');
    }
}
