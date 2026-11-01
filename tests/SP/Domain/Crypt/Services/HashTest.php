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

namespace SP\Tests\Domain\Crypt\Services;

use Faker\Factory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Crypt\Services\Hash;
use SP\Tests\UnitaryTestCase;

/**
 * Class HashTest
 *
 */
#[Group('unitary')]
class HashTest extends UnitaryTestCase
{
    private const HMAC_MESSAGE_1 = 'Synthetic message for HMAC vector one.';
    private const HMAC_KEY_1 = 'synthetic-hmac-key-one';
    private const HMAC_SIGNATURE_1 = '49b8346226bf73d7544bb567ba3fa0e0a03c8341274159c1355e3da06eea4b86';

    private const HMAC_MESSAGE_2 = 'Synthetic message for HMAC vector two.';
    private const HMAC_KEY_2 = 'synthetic-hmac-key-two';
    private const HMAC_SIGNATURE_2 = 'bb42c9a2d1dbcd33917336b8d603454fa7e4aa784c69fc4674e2c2aa7b43ac9a';

    private const PASSWORD = 'Synthetic-Password-123!';
    private const PASSWORD_HASH = '$2y$10$7CVeEvLVzkkOtWEVd9az.OEpIjxWorZfopEUlKwYe98z2ISXLZa2.';

    private const KEY_72 = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const KEY_72_HASH = '$2y$10$fhWqIelg/8gkQTULiq7QuO/UXGPmmzemE9XidSiOUMkkkaB694WtG';

    private const KEY_73 = 'kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk';
    private const KEY_73_HASH = '$2y$10$gUFHjUL2StRCPN8NGnfOLORH1IGFvtJaSDea5JuKzjsK7x8YmpMii';

    private const MULTIBYTE_KEY = 'ééééééééééééééééééééééééééééééééééééééééééééééééééééééééééééééééééééééééé';
    private const MULTIBYTE_KEY_HASH = '$2y$10$TIkE.MKq8IefJdNIZV/hPuj6jwCPpuXw/R7V.8OjI551pwUjydmzK';

    #[Test]
    public function testSignMessageMatchesFixedHmacVectors()
    {
        $this->assertSame(
            self::HMAC_SIGNATURE_1,
            Hash::signMessage(self::HMAC_MESSAGE_1, self::HMAC_KEY_1)
        );
        $this->assertSame(
            self::HMAC_SIGNATURE_2,
            Hash::signMessage(self::HMAC_MESSAGE_2, self::HMAC_KEY_2)
        );
    }

    #[Test]
    public function testCheckMessageWithFixedHmacVectors()
    {
        $this->assertTrue(
            Hash::checkMessage(self::HMAC_MESSAGE_1, self::HMAC_KEY_1, self::HMAC_SIGNATURE_1)
        );
        $this->assertTrue(
            Hash::checkMessage(self::HMAC_MESSAGE_2, self::HMAC_KEY_2, self::HMAC_SIGNATURE_2)
        );

        $this->assertFalse(
            Hash::checkMessage('A different message', self::HMAC_KEY_1, self::HMAC_SIGNATURE_1)
        );
        $this->assertFalse(
            Hash::checkMessage(self::HMAC_MESSAGE_1, 'a-different-key', self::HMAC_SIGNATURE_1)
        );
        $this->assertFalse(
            Hash::checkMessage(self::HMAC_MESSAGE_1, self::HMAC_KEY_1, str_repeat('0', 64))
        );
    }

    #[Test]
    public function testCheckHashKeyVerifiesLegacyBcryptHash()
    {
        $this->assertTrue(Hash::checkHashKey(self::PASSWORD, self::PASSWORD_HASH));
        $this->assertFalse(Hash::checkHashKey('Wrong-Password-123!', self::PASSWORD_HASH));
    }

    #[Test]
    public function testCheckHashKeyVerifiesLegacyShortenedBcryptHash()
    {
        $this->assertSame(Hash::MAX_KEY_LENGTH + 1, strlen(self::KEY_73));
        $this->assertTrue(Hash::checkHashKey(self::KEY_73, self::KEY_73_HASH));
        $this->assertFalse(Hash::checkHashKey(self::KEY_72, self::KEY_73_HASH));
    }

    #[Test]
    public function testCheckHashKeyVerifiesLegacyMultibyteShortenedBcryptHash()
    {
        $this->assertSame(Hash::MAX_KEY_LENGTH + 1, mb_strlen(self::MULTIBYTE_KEY));
        $this->assertGreaterThan(Hash::MAX_KEY_LENGTH, strlen(self::MULTIBYTE_KEY));

        $this->assertTrue(Hash::checkHashKey(self::MULTIBYTE_KEY, self::MULTIBYTE_KEY_HASH));
    }

    #[Test]
    public function testHashKeyAtBoundaryLengths()
    {
        $this->assertSame(Hash::MAX_KEY_LENGTH, strlen(self::KEY_72));
        $this->assertTrue(Hash::checkHashKey(self::KEY_72, self::KEY_72_HASH));

        $this->assertSame(Hash::MAX_KEY_LENGTH + 1, strlen(self::KEY_73));
        $this->assertTrue(Hash::checkHashKey(self::KEY_73, self::KEY_73_HASH));
    }

    #[Test]
    public function testHashKeyProducesBcryptHashThatVerifies()
    {
        foreach ([self::PASSWORD, self::KEY_72, self::KEY_73, self::MULTIBYTE_KEY] as $key) {
            $hash = Hash::hashKey($key);

            $this->assertNotEmpty($hash);
            $this->assertSame(PASSWORD_BCRYPT, password_get_info($hash)['algo']);
            $this->assertTrue(Hash::checkHashKey($key, $hash));
        }
    }

    #[Test]
    public function testHashKey()
    {
        for ($i = 2; $i <= 128; $i *= 2) {
            $key = self::$faker->password(2, $i);
            $hash = Hash::hashKey($key);

            $this->assertNotEmpty($hash);
            $this->assertTrue(Hash::checkHashKey($key, $hash));
        }
    }

    #[Test]
    public function testSignMessage()
    {
        $faker = Factory::create();

        for ($i = 2; $i <= 128; $i *= 2) {
            $text = $faker->text;

            $key = self::$faker->password(2, $i);
            $hash = Hash::signMessage($text, $key);

            $this->assertNotEmpty($hash);
            $this->assertTrue(Hash::checkMessage($text, $key, $hash));
        }
    }
}
