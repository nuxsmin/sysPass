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

namespace SPT\Infrastructure\Database;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\DatabaseException;
use SP\Infrastructure\Database\PDOWrapper;
use SPT\UnitaryTestCase;

/**
 * Class PDOWrapperTest
 */
#[Group('unitary')]
class PDOWrapperTest extends UnitaryTestCase
{

    /**
     * @throws DatabaseException
     * @throws Exception
     */
    public function testBuild()
    {
        $dsn = 'mysql:charset=utf8;host=localhost;port=3306;dbname=test';
        $connectionData = $this->createMock(DatabaseConnectionData::class);

        $connectionData->expects($this->once())
                       ->method('getDbUser');
        $connectionData->expects($this->once())
                       ->method('getDbPass');

        $pdoWrapper = new PDOWrapper();

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unable to connect to DB');

        $pdoWrapper->build($dsn, $connectionData, []);
    }
}
