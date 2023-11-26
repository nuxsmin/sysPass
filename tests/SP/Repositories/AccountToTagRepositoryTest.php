<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Repositories;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\DataModel\ItemData;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Account\Ports\AccountToTagRepositoryInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Account\Repositories\AccountToTagRepository;
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
     * @var AccountToTagRepositoryInterface
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass(): void
    {
        $dic = setupContext();

        self::$loadFixtures = true;

        // Inicializar el repositorio
        self::$repository = $dic->get(AccountToTagRepository::class);
    }

    /**
     * Comprobar la obtención de etiquetas por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetTagsByAccountId()
    {
        $this->assertEquals(2, self::$repository->getTagsByAccountId(1)->getNumRows());
        $this->assertEquals(0, self::$repository->getTagsByAccountId(10)->getNumRows());
    }

    /**
     * Comprobar la creación de etiquetas asociadas a las cuentas
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->tags = [3];

        $this->assertEquals(1, self::$repository->add($accountRequest));

        $result = self::$repository->getTagsByAccountId($accountRequest->id);
        $data = $result->getDataAsArray();

        $this->assertCount(3, $data);
        $this->assertInstanceOf(ItemData::class, $data[0]);
        $this->assertInstanceOf(ItemData::class, $data[1]);
        $this->assertInstanceOf(ItemData::class, $data[2]);

        $this->expectException(ConstraintException::class);

        $accountRequest->tags = [1];

        self::$repository->add($accountRequest);

        $accountRequest->id = 10;

        self::$repository->add($accountRequest);
    }


    /**
     * Comprobar la eliminación de etiquetas por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByAccountId()
    {
        $this->assertEquals(2, self::$repository->deleteByAccountId(1));

        $this->assertEquals(0, self::$repository->getTagsByAccountId(1)->getNumRows());

        $this->assertEquals(0, self::$repository->deleteByAccountId(10));
    }

    /**
     * Comprobar la actualización de etiquetas por Id de cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->tags = [1, 2];

        self::$repository->update($accountRequest);

        $result = self::$repository->getTagsByAccountId($accountRequest->id);
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertInstanceOf(ItemData::class, $data[0]);
        $this->assertInstanceOf(ItemData::class, $data[1]);
    }
}
