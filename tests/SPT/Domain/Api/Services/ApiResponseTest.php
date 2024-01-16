<?php
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

namespace SPT\Domain\Api\Services;

use SP\Domain\Api\Dtos\ApiResponse;
use SPT\UnitaryTestCase;

/**
 * Class ApiResponseTest
 *
 * @group unitary
 */
class ApiResponseTest extends UnitaryTestCase
{

    public function testMakeSuccess()
    {
        $args = [
            self::$faker->name,
            self::$faker->text,
            self::$faker->randomNumber(),
        ];

        $out = ApiResponse::makeSuccess(...$args)->getResponse();

        $this->assertArrayHasKey('result', $out);
        $this->assertArrayHasKey('itemId', $out);
        $this->assertArrayHasKey('resultCode', $out);
        $this->assertArrayHasKey('resultMessage', $out);
        $this->assertArrayHasKey('count', $out);

        $this->assertEquals($args[0], $out['result']);
        $this->assertEquals($args[1], $out['resultMessage']);
        $this->assertEquals($args[2], $out['itemId']);
        $this->assertEquals(0, $out['resultCode']);
        $this->assertNull($out['count']);
    }

    public function testMakeError()
    {
        $args = [
            self::$faker->name,
            self::$faker->text,
        ];

        $out = ApiResponse::makeError(...$args)->getResponse();

        $this->assertArrayHasKey('result', $out);
        $this->assertArrayHasKey('itemId', $out);
        $this->assertArrayHasKey('resultCode', $out);
        $this->assertArrayHasKey('resultMessage', $out);
        $this->assertArrayHasKey('count', $out);

        $this->assertEquals($args[0], $out['result']);
        $this->assertEquals($args[1], $out['resultMessage']);
        $this->assertEquals(1, $out['resultCode']);
        $this->assertNull($out['itemId']);
        $this->assertNull($out['count']);
    }
}
