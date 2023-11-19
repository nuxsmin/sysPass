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

namespace SP\Tests\Core\Crypt;

use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\CryptException;
use SP\Tests\UnitaryTestCase;

/**
 * Class VaultTest
 *
 * @group unitary
 */
class VaultTest extends UnitaryTestCase
{
    /**
     * @throws CryptException
     */
    public function testGetData()
    {
        $data = self::$faker->text;
        $key = self::$faker->password;

        $vault = Vault::factory(new Crypt())->saveData($data, $key);
        $this->assertEquals($data, $vault->getData($key));
    }

    /**
     * @throws CryptException
     */
    public function testGetDataWithNoData()
    {
        $key = self::$faker->password;

        $vault = Vault::factory(new Crypt());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Either data or key must be set');

        $vault->getData($key);
    }

    /**
     * @throws CryptException
     */
    public function testGetTimeSet()
    {
        $vault = Vault::factory(new Crypt())->saveData(self::$faker->text, self::$faker->password);
        $this->assertTrue($vault->getTimeSet() !== 0);
    }

    /**
     * @throws CryptException
     */
    public function testReKey()
    {
        $data = self::$faker->text;
        $key = self::$faker->password;

        $vault = Vault::factory(new Crypt())->saveData($data, $key);

        $newKey = self::$faker->password;

        $vaultRekey = $vault->reKey($newKey, $key);

        $this->assertEquals($data, $vaultRekey->getData($newKey));
        $this->assertGreaterThanOrEqual($vault->getTimeSet(), $vaultRekey->getTimeSet());
    }
}
