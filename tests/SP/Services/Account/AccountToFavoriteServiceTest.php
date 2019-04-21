<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Services\Account;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Services\Account\AccountToFavoriteService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountFavoriteServiceTest
 *
 * @package SP\Tests\Services
 */
class AccountToFavoriteServiceTest extends DatabaseTestCase
{
    /**
     * @var AccountToFavoriteService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_accountFavorite.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(AccountToFavoriteService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$service->delete(1, 3));
        $this->assertEquals(0, self::$service->delete(10, 1));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetForUserId()
    {
        $data = self::$service->getForUserId(3);

        $this->assertCount(2, $data);
        $this->assertArrayHasKey(1, $data);
        $this->assertArrayHasKey(2, $data);
        $this->assertEquals(3, $data[1]);
        $this->assertEquals(3, $data[2]);

        $this->assertCount(0, self::$service->getForUserId(10));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $this->assertEquals(0, self::$service->add(1, 2));

        $this->expectException(ConstraintException::class);

        self::$service->add(3, 1);

        self::$service->add(1, 3);
    }
}
