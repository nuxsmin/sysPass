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

namespace SP\Tests\Infrastructure\Account\Repositories;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\FileData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Out\SimpleModel;
use SP\Infrastructure\Account\Repositories\AccountFileRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountFileRepositoryTest
 */
class AccountFileRepositoryTest extends UnitaryTestCase
{
    private DatabaseInterface|MockObject $databaseInterface;
    private AccountFileRepository        $accountFileRepository;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch()
    {
        $ids = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $values = $arg->getQuery()->getBindValues();

                return array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->deleteByIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatchWithNoIds()
    {
        $this->databaseInterface->expects(self::never())
            ->method('doQuery');

        $this->assertEquals(0, $this->accountFileRepository->deleteByIdBatch([]));
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->getAll();
    }

    public function testGetInfoById()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->getInfoById($id);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testCreate()
    {
        $fileData = $this->buildFileData();

        $expected = new QueryResult();
        $expected->setLastId(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($fileData) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return $params['accountId'] === $fileData->accountId
                       && $params['name'] === $fileData->name
                       && $params['type'] === $fileData->type
                       && $params['thumb'] === $fileData->thumb
                       && $params['content'] === $fileData->content
                       && $params['extension'] === $fileData->extension
                       && $params['size'] === $fileData->size
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getLastId(), $this->accountFileRepository->create($fileData));
    }

    private function buildFileData(): FileData
    {
        $fileData = new FileData();
        $fileData->id = self::$faker->randomNumber();
        $fileData->accountId = self::$faker->randomNumber();
        $fileData->name = self::$faker->name;
        $fileData->type = self::$faker->randomNumber();
        $fileData->thumb = self::$faker->image();
        $fileData->content = self::$faker->image();
        $fileData->extension = self::$faker->name();
        $fileData->size = self::$faker->randomNumber();

        return $fileData;
    }

    public function testGetByAccountId()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['accountId'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->getByAccountId($id);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testGetByIdBatch()
    {
        $ids = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $query = $arg->getQuery();
                $values = $query->getBindValues();

                return array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->getByIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDelete()
    {
        $id = 1;
        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['id'] === $id
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountFileRepository->delete($id));
    }

    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->getById($id);
    }

    public function testSearch()
    {
        $item = new ItemSearchData();
        $item->seachString = self::$faker->name;

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $params = $arg->getQuery()->getBindValues();
                $searchStringLike = '%'.$item->seachString.'%';

                return $params['name'] === $searchStringLike
                       && $params['clientName'] === $searchStringLike
                       && $params['accountName'] === $searchStringLike
                       && $params['type'] === $searchStringLike
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->search($item);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseInterface = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->accountFileRepository = new AccountFileRepository(
            $this->databaseInterface,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
