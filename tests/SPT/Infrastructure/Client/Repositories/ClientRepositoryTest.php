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

namespace SPT\Infrastructure\Client\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use Exception;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Client\Models\Client;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Client\Repositories\ClientRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\ClientGenerator;
use SPT\UnitaryTestCase;

/**
 * Class ClientRepositoryTest
 *
 * @group unitary
 */
class ClientRepositoryTest extends UnitaryTestCase
{

    private ClientRepository             $clientRepository;
    private DatabaseInterface|MockObject $database;

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        $client = ClientGenerator::factory()->buildClient();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($client) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === $client->getName()
                       && $params['hash'] === $client->getHash()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($client) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['name'] === $client->getName()
                       && $params['description'] === $client->getDescription()
                       && $params['isGlobal'] === $client->getIsGlobal()
                       && !empty($params['hash'])
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('doQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackCreate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->clientRepository->create($client);
    }

    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreateWithDuplicate()
    {
        $client = ClientGenerator::factory()->buildClient();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($client) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === $client->getName()
                       && !empty($params['hash'])
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callbackDuplicate)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Duplicated client');

        $this->clientRepository->create($client);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['id'] === $id
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())->method('doQuery')->with($callback);

        $this->clientRepository->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $ids = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $query = $arg->getQuery();
                $values = $query->getBindValues();

                return count($values) === 3
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === Simple::class
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback);

        $this->clientRepository->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database
            ->expects(self::never())
            ->method('doQuery');

        $this->clientRepository->deleteByIdBatch([]);
    }

    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && $arg->getMapClassName() === Client::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->clientRepository->getById($id);
    }

    /**
     * @throws Exception
     */
    public function testSearch()
    {
        $item = new ItemSearchData(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 2
                       && $params['name'] === $searchStringLike
                       && $params['description'] === $searchStringLike
                       && $arg->getMapClassName() === Client::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback, true);

        $this->clientRepository->search($item);
    }

    /**
     * @throws Exception
     */
    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return count($query->getBindValues()) === 0
                       && $arg->getMapClassName() === Client::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback, true);

        $this->clientRepository->search(new ItemSearchData());
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === Client::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->clientRepository->getAll();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetAllForFilter()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === Simple::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $select = (new QueryFactory('mysql', QueryFactory::COMMON))->newSelect();

        $accountFilterUser = $this->createMock(AccountFilterBuilder::class);
        $accountFilterUser->expects(self::once())
                          ->method('buildFilter')
                          ->willReturn($select);

        $this->clientRepository->getAllForFilter($accountFilterUser);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $client = ClientGenerator::factory()->buildClient();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($client) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['id'] === $client->getId()
                       && $params['name'] === $client->getName()
                       && !empty($params['hash'])
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($client) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 5
                       && $params['id'] === $client->getId()
                       && $params['name'] === $client->getName()
                       && $params['description'] === $client->getDescription()
                       && $params['isGlobal'] === $client->getIsGlobal()
                       && !empty($params['hash'])
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('doQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackUpdate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->clientRepository->update($client);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckDuplicatedOnUpdate()
    {
        $client = ClientGenerator::factory()->buildClient();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($client) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['id'] === $client->getId()
                       && $params['name'] === $client->getName()
                       && !empty($params['hash'])
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callbackDuplicate)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Duplicated client');

        $this->clientRepository->update($client);
    }

    public function testGetByName()
    {
        $name = self::$faker->colorName();

        $callback = new Callback(
            static function (QueryData $arg) use ($name) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['name'] === $name
                       && !empty($params['hash'])
                       && $arg->getMapClassName() === Client::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->clientRepository->getByName($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->clientRepository = new ClientRepository(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
