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

namespace SPT\Domain\Client\Services;

use Aura\SqlQuery\QueryFactory;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Client\Ports\ClientRepository;
use SP\Domain\Client\Services\Client;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\ClientGenerator;
use SPT\Generators\ItemSearchDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class CategoryTest
 *
 */
#[Group('unitary')]
class ClientTest extends UnitaryTestCase
{

    private ClientRepository|MockObject $clientRepository;
    private Client                      $client;
    private AccountFilterBuilder|Stub   $accountFilterUser;

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $client = ClientGenerator::factory()->buildClient();

        $this->clientRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$client]));

        $out = $this->client->getById($id);

        $this->assertEquals($client, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByIdWithUnknownId()
    {
        $id = self::$faker->randomNumber();

        $this->clientRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Client not found');

        $this->client->getById($id);
    }

    /**
     * @throws Exception
     */
    public function testSearch()
    {
        $itemSearch = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->clientRepository
            ->expects(self::once())
            ->method('search')
            ->with($itemSearch);

        $this->client->search($itemSearch);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $queryResult = new QueryResult([1]);

        $this->clientRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn($queryResult->setAffectedNumRows(1));

        $this->client->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteWithNotFound()
    {
        $id = self::$faker->randomNumber();

        $this->clientRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Client not found');

        $this->client->delete($id);
    }

    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreate()
    {
        $client = ClientGenerator::factory()->buildClient();

        $queryResult = new QueryResult();
        $queryResult->setLastId(self::$faker->randomNumber());

        $this->clientRepository
            ->expects(self::once())
            ->method('create')
            ->with($client)
            ->willReturn($queryResult);

        $out = $this->client->create($client);

        $this->assertEquals($queryResult->getLastId(), $out);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $client = ClientGenerator::factory()->buildClient();

        $this->clientRepository
            ->expects(self::once())
            ->method('update')
            ->with($client)
            ->willReturn(1);

        $this->client->update($client);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $name = self::$faker->colorName();

        $client = ClientGenerator::factory()->buildClient();

        $this->clientRepository
            ->expects(self::once())
            ->method('getByName')
            ->with($name)
            ->willReturn(new QueryResult([$client]));

        $out = $this->client->getByName($name);

        $this->assertEquals($client, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByNameWithUnknownName()
    {
        $name = self::$faker->colorName();

        $this->clientRepository
            ->expects(self::once())
            ->method('getByName')
            ->with($name)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Client not found');

        $this->client->getByName($name);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $queryResult = new QueryResult();

        $this->clientRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn($queryResult->setAffectedNumRows(1));

        $this->client->deleteByIdBatch($ids);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testDeleteByIdBatchError()
    {
        $ids = array_map(fn() => self::$faker->randomNumber(), range(0, 4));

        $queryResult = new QueryResult();

        $this->clientRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn($queryResult->setAffectedNumRows(0));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the clients');

        $this->client->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $client = ClientGenerator::factory()->buildClient();

        $this->clientRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([$client]));

        $out = $this->client->getAll();

        $this->assertEquals([$client], $out);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetAllForUser()
    {
        $client = ClientGenerator::factory()->buildClient();

        $this->clientRepository
            ->expects(self::once())
            ->method('getAllForFilter')
            ->with($this->accountFilterUser)
            ->willReturn(new QueryResult([$client]));

        $out = $this->client->getAllForUser();

        $this->assertEquals([$client], $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRepository = $this->createMock(ClientRepository::class);

        $select = (new QueryFactory('mysql', QueryFactory::COMMON))->newSelect();

        $this->accountFilterUser = $this->createStub(AccountFilterBuilder::class);
        $this->accountFilterUser->method('buildFilter')
                                ->willReturn($select);

        $this->client = new Client($this->application, $this->clientRepository, $this->accountFilterUser);
    }
}
