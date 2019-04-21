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

namespace SP\Tests\Repositories;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Repositories\User\UserPassRecoverRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\PasswordUtil;
use function SP\Tests\setupContext;

/**
 * Class UserPassRecoverRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class UserPassRecoverRepositoryTest extends DatabaseTestCase
{
    /**
     * @var UserPassRecoverRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(UserPassRecoverRepository::class);
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $this->assertEquals(3, self::$repository->add(2, PasswordUtil::generateRandomBytes()));

        $this->expectException(ConstraintException::class);

        self::$repository->add(10, PasswordUtil::generateRandomBytes());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAttemptsByUserId()
    {
        $this->assertEquals(2, self::$repository->getAttemptsByUserId(2, 1529275206));

        $this->assertEquals(0, self::$repository->getAttemptsByUserId(3, 1529275206));

        $this->assertEquals(0, self::$repository->getAttemptsByUserId(10, 1529275206));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws EnvironmentIsBrokenException
     */
    public function testGetUserIdForHash()
    {
        $result = self::$repository->getUserIdForHash(pack('H*', '3038366162313036303866363838346566383031396134353237333561633066'), 1529275200);

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals(2, $result->getData()->userId);

        $result = self::$repository->getUserIdForHash(PasswordUtil::generateRandomBytes(), 1529275206);

        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws SPException
     */
    public function testToggleUsedByHash()
    {
        $result = self::$repository->toggleUsedByHash(pack('H*', '3038366162313036303866363838346566383031396134353237333561633066'), 1529275200);

        $this->assertEquals(1, $result);

        $result = self::$repository->toggleUsedByHash(pack('H*', '3038366162313036303866363838346566383031396134353237333561633066'), 1529275200);

        $this->assertEquals(0, $result);

        $result = self::$repository->toggleUsedByHash(pack('H*', '3532383335346130663366626661376161626538303831373231653065633631'), 1529275331);

        $this->assertEquals(0, $result);
    }
}
