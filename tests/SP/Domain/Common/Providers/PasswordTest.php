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

namespace SP\Tests\Domain\Common\Providers;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use SP\Domain\Common\Providers\Password;

/**
 * Class PasswordUtilTest
 */
#[Group('unitary')]
class PasswordTest extends TestCase
{

    public function testCheckStrength()
    {
        $passwordLower = str_split('artpwerlm');
        $passwordUpper = str_split('AFGUYOHQEM');
        $passwordSpecial = str_split('%._-$&/()');
        $passwordNumber = str_split('18675249');

        $this->assertEquals(count($passwordLower), Password::checkStrength($passwordLower)['lower']);
        $this->assertEquals(count($passwordUpper), Password::checkStrength($passwordUpper)['upper']);
        $this->assertEquals(count($passwordSpecial), Password::checkStrength($passwordSpecial)['special']);
        $this->assertEquals(count($passwordNumber), Password::checkStrength($passwordNumber)['number']);

        $passwordMixed = array_merge($passwordLower, $passwordUpper, $passwordSpecial, $passwordNumber);
        shuffle($passwordMixed);

        foreach (Password::checkStrength($passwordMixed) as $count) {
            $this->assertGreaterThan(0, $count);
        }
    }

    /**
     * @throws RandomException
     */
    public function testRandomPassword()
    {
        $lengths = [16, 32, 64];

        foreach ($lengths as $length) {
            $pass = Password::randomPassword($length);

            $this->assertEquals($length, strlen($pass));

            foreach (\SP\Domain\Common\Providers\Password::checkStrength(str_split($pass)) as $type => $count) {
                $this->assertGreaterThan(0, $count);
            }
        }
    }

    /**
     * @throws RandomException
     */
    public function testRandomPasswordNoFlags()
    {
        $pass = Password::randomPassword(16, 0);
        $strength = Password::checkStrength(str_split($pass));

        $this->assertGreaterThan(0, $strength['lower']);
        $this->assertGreaterThan(0, $strength['upper']);
        $this->assertEquals(0, $strength['special']);
        $this->assertEquals(0, $strength['number']);
    }

    /**
     * @throws RandomException
     */
    public function testRandomPasswordSpecial()
    {
        $flags = \SP\Domain\Common\Providers\Password::FLAG_PASSWORD_SPECIAL |
                 \SP\Domain\Common\Providers\Password::FLAG_PASSWORD_STRENGTH;
        $pass = Password::randomPassword(16, $flags);
        $strength = \SP\Domain\Common\Providers\Password::checkStrength(str_split($pass));

        $this->assertGreaterThan(0, $strength['lower']);
        $this->assertGreaterThan(0, $strength['upper']);
        $this->assertGreaterThan(0, $strength['special']);
        $this->assertEquals(0, $strength['number']);
    }

    /**
     * @throws RandomException
     */
    public function testRandomPasswordNumbers()
    {
        $flags = Password::FLAG_PASSWORD_NUMBER | Password::FLAG_PASSWORD_STRENGTH;
        $pass = Password::randomPassword(16, $flags);
        $strength = Password::checkStrength(str_split($pass));

        $this->assertGreaterThan(0, $strength['lower']);
        $this->assertGreaterThan(0, $strength['upper']);
        $this->assertGreaterThan(0, $strength['number']);
        $this->assertEquals(0, $strength['special']);
    }


    /**
     * @throws RandomException
     */
    public function testRandomPasswordAll()
    {
        $flags = \SP\Domain\Common\Providers\Password::FLAG_PASSWORD_NUMBER | Password::FLAG_PASSWORD_SPECIAL |
                 Password::FLAG_PASSWORD_STRENGTH;
        $pass = Password::randomPassword(16, $flags);
        $strength = Password::checkStrength(str_split($pass));

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
        $bytesHex = Password::generateRandomBytes(16);

        $this->assertEquals(32, strlen($bytesHex));

        $bytesHex = \SP\Domain\Common\Providers\Password::generateRandomBytes(32);

        $this->assertEquals(64, strlen($bytesHex));

        $bytesHex = Password::generateRandomBytes(64);

        $this->assertEquals(128, strlen($bytesHex));
    }
}
