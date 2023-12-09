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

use Faker\Factory;
use SP\Core\Crypt\Hash;
use SPT\UnitaryTestCase;

/**
 * Class HashTest
 *
 * @group unitary
 */
class HashTest extends UnitaryTestCase
{
    public function testHashKey()
    {
        for ($i = 2; $i <= 128; $i *= 2) {
            $key = self::$faker->password(2, $i);
            $hash = Hash::hashKey($key);

            $this->assertNotEmpty($hash);
            $this->assertTrue(Hash::checkHashKey($key, $hash));
        }
    }

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
