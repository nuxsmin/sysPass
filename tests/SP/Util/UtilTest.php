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

namespace SP\Tests\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SP\Tests\UnitaryTestCase;
use SP\Util\Util;

/**
 * Class UtilTest
 */
#[Group('unitary')]
class UtilTest extends UnitaryTestCase
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
            ['131072', 131072],
            ['134217728', 134217728],
            ['137438953472', 137438953472],
        ];
    }

    #[DataProvider('unitsProvider')]
    public function testConvertShortUnit(string $unit, int $expected)
    {
        $this->assertEquals($expected, Util::convertShortUnit($unit));
    }

    public function testGetMaxUpload()
    {
        $upload = ini_get('upload_max_filesize',);
        $post = ini_get('post_max_size');
        $memory = ini_get('memory_limit');

        $this->assertEquals(min($upload, $post, $memory), Util::getMaxUpload());
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
}
