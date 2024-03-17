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

namespace SPT\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SP\Util\Util;

/**
 * Class UtilTest
 *
 * @package SPT\Util
 */
class UtilTest extends TestCase
{
    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass(): void
    {
        ini_set('memory_limit', -1);
    }

    public static function boolProvider(): array
    {
        return [
            ['false', false],
            ['no', false],
            ['n', false],
            ['0', false],
            ['off', false],
            [0, false],
            ['true', true],
            ['yes', true],
            ['y', true],
            ['1', true],
            ['on', true],
            [1, true]
        ];
    }

    public static function unitsProvider(): array
    {
        return [
            ['128K', 131072],
            ['128M', 134217728],
            ['128G', 137438953472],
            [131072, 131072],
            [134217728, 134217728],
            [137438953472, 137438953472],
        ];
    }

    public function testCastToClass()
    {
        self::markTestIncomplete();
    }

    public function testUnserialize()
    {
        self::markTestIncomplete();
    }

    /**
     * @param $unit
     * @param $expected
     */
    #[DataProvider('unitsProvider')]
    public function testConvertShortUnit($unit, $expected)
    {
        $this->assertEquals($expected, Util::convertShortUnit($unit));
    }

    public function testGetMaxUpload()
    {
        $upload = ini_set('upload_max_filesize', '30M');
        $post = ini_set('post_max_size', '10M');
        $memory = ini_set('memory_limit', memory_get_usage() * 1.5);

        if ($upload !== false
            && $post !== false
            && $memory !== false
        ) {
            $this->assertEquals(10485760, Util::getMaxUpload());
        } else {
            self::markTestSkipped('Unable to set PHP\'s ini variables');
        }
    }

    /**
     * @param $value
     * @param $expected
     */
    #[DataProvider('boolProvider')]
    public function testBoolval($value, $expected)
    {
        $this->assertEquals($expected, Util::boolval($value));
        $this->assertEquals($expected, Util::boolval($value, true));
    }

    public function testGetTempDir()
    {
        self::markTestIncomplete();
    }
}
