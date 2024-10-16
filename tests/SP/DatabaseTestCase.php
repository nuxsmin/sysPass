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

namespace SP\Tests;

use PHPUnit\Framework\TestCase;

define('APP_MODULE', 'web-test');

/**
 * Class DatabaseBaseTest
 *
 * Caso de test para tests que requieran consultas a la BBDD
 *
 * @package SP\Tests
 */
abstract class DatabaseTestCase extends TestCase
{
    use DatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$loadFixtures) {
            self::loadFixtures();
        }
    }
}
