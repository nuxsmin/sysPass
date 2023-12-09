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

namespace SPT\Services\UserPassRecover;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserPassRecoverServiceInterface;
use SP\Domain\User\Services\UserPassRecoverService;
use SP\Util\PasswordUtil;
use SPT\DatabaseTestCase;

use function SPT\setupContext;

/**
 * Class UserPassRecoverServiceTest
 *
 * @package SPT\SP\Domain\Common\Services\UserPassRecover
 */
class UserPassRecoverServiceTest extends DatabaseTestCase
{
    /**
     * @var UserPassRecoverServiceInterface
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     * @throws SPException
     */
    public static function setUpBeforeClass(): void
    {
        $dic = setupContext();

        self::$loadFixtures = true;

        // Inicializar el servicio
        self::$service = $dic->get(UserPassRecoverService::class);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     * @throws SPException
     */
    public function testToggleUsedByHash()
    {
        self::$service->toggleUsedByHash(self::$service->requestForUserId(2));

        $this->expectException(ServiceException::class);

        self::$service->toggleUsedByHash(PasswordUtil::generateRandomBytes());
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testToggleUsedByHashExpired()
    {
        $this->expectException(ServiceException::class);

        self::$service->toggleUsedByHash(pack('H*', '3038366162313036303866363838346566383031396134353237333561633066'));
    }

    /**
     * @throws ConstraintException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     */
    public function testAdd()
    {
        $this->assertEquals(3, self::$service->add(2, PasswordUtil::generateRandomBytes()));

        $this->expectException(ConstraintException::class);

        self::$service->add(10, PasswordUtil::generateRandomBytes());
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
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
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
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
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testGetUserIdForHash()
    {
        $result = self::$service->getUserIdForHash(self::$service->requestForUserId(2));

        $this->assertEquals(2, $result);

        $this->expectException(ServiceException::class);

        self::$service->getUserIdForHash(PasswordUtil::generateRandomBytes());
    }
}
