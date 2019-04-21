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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use SP\Core\Crypt\Hash;
use SP\Util\PasswordUtil;

/**
 * Class HashTest
 *
 * @package SP\Tests\SP\Core\Crypt
 */
class HashTest extends TestCase
{
    /**
     * @throws EnvironmentIsBrokenException
     */
    public function testHashKey()
    {
        for ($i = 2; $i <= 128; $i *= 2) {
            $key = PasswordUtil::generateRandomBytes($i);
            $hash = Hash::hashKey($key);

            $this->assertNotEmpty($hash);
            $this->assertTrue(Hash::checkHashKey($key, $hash));
        }
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function testSignMessage()
    {
        $faker = Factory::create();

        for ($i = 2; $i <= 128; $i *= 2) {
            $text = $faker->text;

            $key = PasswordUtil::generateRandomBytes($i);
            $hash = Hash::signMessage($text, $key);

            $this->assertNotEmpty($hash);
            $this->assertTrue(Hash::checkMessage($text, $key, $hash));
        }
    }
}
