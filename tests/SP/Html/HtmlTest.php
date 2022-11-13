<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Html;

use Faker\Factory;
use PHPUnit\Framework\TestCase;
use SP\Html\Html;

/**
 * Class HtmlTest
 */
class HtmlTest extends TestCase
{
    private static $faker;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$faker = Factory::create();
    }


    public function testGetSafeUrlOk()
    {
        $url = self::$faker->url;

        $this->assertEquals($url, Html::getSafeUrl($url));
    }

    /**
     * @dataProvider urlProvider
     * @return void
     */
    public function testGetSafeUrlEncoded(string $url)
    {
        $this->assertEquals(0, preg_match('/["<>\']+/', Html::getSafeUrl($url)));
    }

    private function urlProvider(): array
    {
        return [
            ['https://foo.com/<script>alert("TEST");</script>'],
            ['https://foo.com/><script>alert("TEST");</script>'],
            ['https://foo.com/"><script>alert("TEST");</script>'],
            ['https://foo.com/"%20onClick="alert(\'TEST\'")'],
            ['https://foo.com/" onClick="alert(\'TEST\')"'],
            ['mongodb+srv://cluster.foo.mongodb.net/bar'],
        ];
    }
}
