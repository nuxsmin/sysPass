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

namespace SP\Tests\Util;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\TestCase;
use SP\Util\PasswordUtil;

/**
 * Class PasswordUtilTest
 *
 * @package SP\Tests\Util
 */
class PasswordUtilTest extends TestCase
{

    public function testCheckStrength()
    {
        $passwordLower = str_split('artpwerlm');
        $passwordUpper = str_split('AFGUYOHQEM');
        $passwordSpecial = str_split('%._-$&/()');
        $passwordNumber = str_split('18675249');

        $this->assertEquals(count($passwordLower), PasswordUtil::checkStrength($passwordLower)['lower']);
        $this->assertEquals(count($passwordUpper), PasswordUtil::checkStrength($passwordUpper)['upper']);
        $this->assertEquals(count($passwordSpecial), PasswordUtil::checkStrength($passwordSpecial)['special']);
        $this->assertEquals(count($passwordNumber), PasswordUtil::checkStrength($passwordNumber)['number']);

        $passwordMixed = array_merge($passwordLower, $passwordUpper, $passwordSpecial, $passwordNumber);
        shuffle($passwordMixed);

        foreach (PasswordUtil::checkStrength($passwordMixed) as $count) {
            $this->assertGreaterThan(0, $count);
        }
    }

    public function testRandomPassword()
    {
        $lengths = [16, 32, 64];

        foreach ($lengths as $length) {
            $pass = PasswordUtil::randomPassword($length);

            $this->assertEquals($length, strlen($pass));

            foreach (PasswordUtil::checkStrength(str_split($pass)) as $type => $count) {
                $this->assertGreaterThan(0, $count);
            }
        }
    }

    public function testRandomPasswordNoFlags()
    {
        $pass = PasswordUtil::randomPassword(16, 0);
        $strength = PasswordUtil::checkStrength(str_split($pass));

        $this->assertGreaterThan(0, $strength['lower']);
        $this->assertGreaterThan(0, $strength['upper']);
        $this->assertEquals(0, $strength['special']);
        $this->assertEquals(0, $strength['number']);
    }

    public function testRandomPasswordSpecial()
    {
        $flags = PasswordUtil::FLAG_PASSWORD_SPECIAL | PasswordUtil::FLAG_PASSWORD_STRENGTH;
        $pass = PasswordUtil::randomPassword(16, $flags);
        $strength = PasswordUtil::checkStrength(str_split($pass));

        $this->assertGreaterThan(0, $strength['lower']);
        $this->assertGreaterThan(0, $strength['upper']);
        $this->assertGreaterThan(0, $strength['special']);
        $this->assertEquals(0, $strength['number']);
    }

    public function testRandomPasswordNumbers()
    {
        $flags = PasswordUtil::FLAG_PASSWORD_NUMBER | PasswordUtil::FLAG_PASSWORD_STRENGTH;
        $pass = PasswordUtil::randomPassword(16, $flags);
        $strength = PasswordUtil::checkStrength(str_split($pass));

        $this->assertGreaterThan(0, $strength['lower']);
        $this->assertGreaterThan(0, $strength['upper']);
        $this->assertGreaterThan(0, $strength['number']);
        $this->assertEquals(0, $strength['special']);
    }

    public function testRandomPasswordAll()
    {
        $flags = PasswordUtil::FLAG_PASSWORD_NUMBER | PasswordUtil::FLAG_PASSWORD_SPECIAL | PasswordUtil::FLAG_PASSWORD_STRENGTH;
        $pass = PasswordUtil::randomPassword(16, $flags);
        $strength = PasswordUtil::checkStrength(str_split($pass));

        $this->assertGreaterThan(0, $strength['lower']);
        $this->assertGreaterThan(0, $strength['upper']);
        $this->assertGreaterThan(0, $strength['number']);
        $this->assertGreaterThan(0, $strength['special']);
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function testGenerateRandomBytes()
    {
        $bytesHex = PasswordUtil::generateRandomBytes(16);

        $this->assertEquals(32, strlen($bytesHex));

        $bytesHex = PasswordUtil::generateRandomBytes(32);

        $this->assertEquals(64, strlen($bytesHex));

        $bytesHex = PasswordUtil::generateRandomBytes(64);

        $this->assertEquals(128, strlen($bytesHex));
    }
}
