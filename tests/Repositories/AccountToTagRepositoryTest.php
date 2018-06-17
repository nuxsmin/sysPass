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

use DI\DependencyException;
use SP\Account\AccountRequest;
use SP\Core\Exceptions\ConstraintException;
use SP\DataModel\ItemData;
use SP\Repositories\Account\AccountToTagRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountToTagRepositoryTest
 *
 * Tests de integración para la comprobación de operaciones de etiquetas asociadas a cuentas
 *
 * @package SP\Tests
 */
class AccountToTagRepositoryTest extends DatabaseTestCase
{
    /**
     * @var AccountToTagRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(AccountToTagRepository::class);
    }

    /**
     * Comprobar la obtención de etiquetas por Id de cuenta
     *
     * @throws ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetTagsByAccountId()
    {
        $this->assertCount(1, self::$repository->getTagsByAccountId(1));
        $this->assertCount(0, self::$repository->getTagsByAccountId(10));
    }

    /**
     * Comprobar la creación de etiquetas asociadas a las cuentas
     *
     * @depends testGetTagsByAccountId
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAdd()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->tags = [2, 3];

        $this->assertEquals(2, self::$repository->add($accountRequest));

        $tags = self::$repository->getTagsByAccountId($accountRequest->id);

        $this->assertCount(3, $tags);
        $this->assertInstanceOf(ItemData::class, $tags[0]);
        $this->assertInstanceOf(ItemData::class, $tags[1]);
        $this->assertInstanceOf(ItemData::class, $tags[2]);

        $this->expectException(ConstraintException::class);

        $accountRequest->tags = [1];

        self::$repository->add($accountRequest);

        $accountRequest->id = 10;

        self::$repository->add($accountRequest);
    }


    /**
     * Comprobar la eliminación de etiquetas por Id de cuenta
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByAccountId()
    {
        $this->assertEquals(1, self::$repository->deleteByAccountId(1));

        $this->assertCount(0, self::$repository->getTagsByAccountId(1));

        $this->assertEquals(0, self::$repository->deleteByAccountId(10));
    }

    /**
     * Comprobar la actualización de etiquetas por Id de cuenta
     *
     * @depends testGetTagsByAccountId
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->tags = [1, 2];

        self::$repository->update($accountRequest);

        $tags = self::$repository->getTagsByAccountId($accountRequest->id);

        $this->assertCount(2, $tags);
        $this->assertInstanceOf(ItemData::class, $tags[0]);
        $this->assertInstanceOf(ItemData::class, $tags[1]);
    }
}
