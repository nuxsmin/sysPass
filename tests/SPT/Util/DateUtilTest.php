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

namespace SPT\Util;

use PHPUnit\Framework\Attributes\Group;
use SP\Util\DateUtil;
use SPT\UnitaryTestCase;

/**
 * Class DateUtilTest
 */
#[Group('unitary')]
class DateUtilTest extends UnitaryTestCase
{
    public function testGetDateFromUnix()
    {
        $out = DateUtil::getDateFromUnix(self::$faker->unixTime());

        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $out);
    }

    public function testGetDateFromUnixFromString()
    {
        $date = self::$faker->date();
        $out = DateUtil::getDateFromUnix($date);

        $this->assertEquals($date, $out);
    }
}
