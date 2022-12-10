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
use SP\Domain\Common\Models\Simple;
use SP\Infrastructure\Account\Repositories\AccountFileRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\FileDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountFileRepositoryTest
 *
 * @group unitary
 */
class AccountFileRepositoryTest extends UnitaryTestCase
{
    private DatabaseInterface|MockObject $database;
    private AccountFileRepository        $accountFileRepository;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch(): void
    {
        $ids = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $values = $arg->getQuery()->getBindValues();

                return array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->deleteByIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database->expects(self::never())
            ->method('doQuery');

        $this->assertEquals(0, $this->accountFileRepository->deleteByIdBatch([]));
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testCreate(): void
    {
        $fileData = FileData::buildFromSimpleModel(FileDataGenerator::factory()->buildFileData());

        $expected = new QueryResult();
        $expected->setLastId(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($fileData) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return $params['accountId'] === $fileData->getAccountId()
                       && $params['name'] === $fileData->getName()
                       && $params['type'] === $fileData->getType()
                       && $params['thumb'] === $fileData->getThumb()
                       && $params['content'] === $fileData->getContent()
                       && $params['extension'] === $fileData->getExtension()
                       && $params['size'] === $fileData->getSize()
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getLastId(), $this->accountFileRepository->create($fileData));
    }

    public function testGetByAccountId(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['accountId'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->getByAccountId($id);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDelete(): void
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

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountFileRepository->delete($id));
    }

    public function testGetById(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->getById($id);
    }

    public function testSearch(): void
    {
        $item = new ItemSearchData(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $params = $arg->getQuery()->getBindValues();
                $searchStringLike = '%'.$item->getSeachString().'%';

                return $params['name'] === $searchStringLike
                       && $params['clientName'] === $searchStringLike
                       && $params['accountName'] === $searchStringLike
                       && $params['type'] === $searchStringLike
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountFileRepository->search($item);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->accountFileRepository = new AccountFileRepository(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
