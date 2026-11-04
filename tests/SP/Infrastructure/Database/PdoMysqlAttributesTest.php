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

namespace SP\Tests\Infrastructure\Database;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Infrastructure\Database\PdoMysqlAttributes;
use SP\Tests\UnitaryTestCase;

/**
 * Class PdoMysqlAttributesTest
 */
#[Group('unitary')]
class PdoMysqlAttributesTest extends UnitaryTestCase
{
    #[Test]
    public function testFoundRows(): void
    {
        $expected = class_exists(\Pdo\Mysql::class)
            ? \Pdo\Mysql::ATTR_FOUND_ROWS
            : PDO::MYSQL_ATTR_FOUND_ROWS;

        self::assertSame($expected, PdoMysqlAttributes::foundRows());
    }

    #[Test]
    public function testUseBufferedQuery(): void
    {
        $expected = class_exists(\Pdo\Mysql::class)
            ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY
            : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;

        self::assertSame($expected, PdoMysqlAttributes::useBufferedQuery());
    }
}
