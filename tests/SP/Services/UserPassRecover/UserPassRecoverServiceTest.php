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

namespace SP\Tests\SP\Services\UserPassRecover;

use SP\Core\Exceptions\ConstraintException;
use SP\Services\ServiceException;
use SP\Services\UserPassRecover\UserPassRecoverService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\Util;
use function SP\Tests\setupContext;

/**
 * Class UserPassRecoverServiceTest
 *
 * @package SP\Tests\SP\Services\UserPassRecover
 */
class UserPassRecoverServiceTest extends DatabaseTestCase
{
    /**
     * @var UserPassRecoverService
     */
    private static $service;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(UserPassRecoverService::class);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testToggleUsedByHash()
    {
        self::$service->toggleUsedByHash(self::$service->requestForUserId(2));

        $this->expectException(ServiceException::class);

        self::$service->toggleUsedByHash(Util::generateRandomBytes());
    }

    /**
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testToggleUsedByHashExpired ()
    {
        $this->expectException(ServiceException::class);

        self::$service->toggleUsedByHash(pack('H*', '3038366162313036303866363838346566383031396134353237333561633066'));
    }

    /**
     * @throws ConstraintException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAdd()
    {
        $this->assertEquals(3, self::$service->add(2, Util::generateRandomBytes()));

        $this->expectException(ConstraintException::class);

        self::$service->add(10, Util::generateRandomBytes());
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testRequestForUserId()
    {
        $hash = self::$service->requestForUserId(2);

        $this->assertNotEmpty($hash);

        $this->assertEquals(2, self::$service->getUserIdForHash($hash));

        $this->expectException(ConstraintException::class);

        self::$service->requestForUserId(10);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCheckAttemptsByUserId()
    {
        $this->assertFalse(self::$service->checkAttemptsByUserId(2));

        for ($i = 1; $i <= UserPassRecoverService::MAX_PASS_RECOVER_LIMIT; $i++) {
            self::$service->requestForUserId(2);
        }

        $this->assertTrue(self::$service->checkAttemptsByUserId(2));

        $this->assertFalse(self::$service->checkAttemptsByUserId(10));
    }

    /**
     * @throws ConstraintException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     */
    public function testGetUserIdForHash()
    {
        $result = self::$service->getUserIdForHash(self::$service->requestForUserId(2));

        $this->assertEquals(2, $result);

        $this->expectException(ServiceException::class);

        self::$service->getUserIdForHash(Util::generateRandomBytes());
    }
}
