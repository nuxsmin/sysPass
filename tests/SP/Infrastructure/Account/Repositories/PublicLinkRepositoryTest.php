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

namespace SP\Tests\Infrastructure\Account\Repositories;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Account\Repositories\PublicLinkRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class PublicLinkRepositoryTest
 *
 * @group unitary
 */
class PublicLinkRepositoryTest extends UnitaryTestCase
{

    private PublicLinkRepository         $publicLinkRepository;
    private MockObject|DatabaseInterface $database;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDelete(): void
    {
        $id = 1;
        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn($expected);

        $this->publicLinkRepository->delete($id);
    }

    public function testSearch(): void
    {
        $item = new ItemSearchData(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $params = $arg->getQuery()->getBindValues();
                $searchStringLike = '%'.$item->getSeachString().'%';

                return $params['login'] === $searchStringLike
                       && $params['accountName'] === $searchStringLike
                       && $params['clientName'] === $searchStringLike
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->publicLinkRepository->search($item);
    }

    public function testGetHashForItem(): void
    {
        $itemId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($itemId) {
                return $arg->getQuery()->getBindValues()['itemId'] === $itemId
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doSelect')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLinkRepository->getHashForItem($itemId);
    }

    public function testGetById(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doSelect')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLinkRepository->getById($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAddLinkView(): void
    {
        $publicLinkData = $this->buildPublicLinkData();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($publicLinkData) {
                $query = $arg->getQuery();
                $values = $query->getBindValues();

                return $values['hash'] === $publicLinkData->getHash()
                       && $values['useInfo'] === $publicLinkData->getUseInfo()
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn($expected);

        $this->assertTrue($this->publicLinkRepository->addLinkView($publicLinkData));
    }

    private function buildPublicLinkData(): PublicLinkData
    {
        $data = [
            'id'              => self::$faker->randomNumber(),
            'itemId'          => self::$faker->randomNumber(),
            'hash'            => self::$faker->sha1,
            'userId'          => self::$faker->randomNumber(),
            'typeId'          => self::$faker->randomNumber(),
            'notify'          => self::$faker->boolean,
            'dateAdd'         => self::$faker->unixTime,
            'dateUpdate'      => self::$faker->unixTime,
            'dateExpire'      => self::$faker->unixTime,
            'countViews'      => self::$faker->randomNumber(),
            'totalCountViews' => self::$faker->randomNumber(),
            'maxCountViews'   => self::$faker->randomNumber(),
            'useInfo'         => self::$faker->text(),
            'data'            => self::$faker->text(),
        ];

        return new PublicLinkData($data);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate(): void
    {
        $publicLinkData = $this->buildPublicLinkData();

        $callbackCheckDuplicate = new Callback(
            static function (QueryData $arg) use ($publicLinkData) {
                $params = $arg->getQuery()->getBindValues();

                return $params['itemId'] === $publicLinkData->getItemId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($publicLinkData) {
                $params = $arg->getQuery()->getBindValues();

                return $params['itemId'] === $publicLinkData->getItemId()
                       && $params['hash'] === $publicLinkData->getHash()
                       && $params['data'] === $publicLinkData->getData()
                       && $params['userId'] === $publicLinkData->getUserId()
                       && $params['typeId'] === $publicLinkData->getTypeId()
                       && $params['notify'] === (int)$publicLinkData->isNotify()
                       && $params['dateExpire'] === $publicLinkData->getDateExpire()
                       && $params['maxCountViews'] === $publicLinkData->getMaxCountViews()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::exactly(2))
                       ->method('doQuery')
                       ->with(...self::withConsecutive([$callbackCheckDuplicate], [$callbackCreate]))
                       ->willReturn(new QueryResult());

        $this->publicLinkRepository->create($publicLinkData);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreateWithDuplicate(): void
    {
        $publicLinkData = $this->buildPublicLinkData();

        $callback = new Callback(
            static function (QueryData $arg) use ($publicLinkData) {
                $params = $arg->getQuery()->getBindValues();

                return $params['itemId'] === $publicLinkData->getItemId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Link already created');

        $this->publicLinkRepository->create($publicLinkData);
    }

    public function testGetAll(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->publicLinkRepository->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testRefresh(): void
    {
        $publicLinkData = $this->buildPublicLinkData();

        $callback = new Callback(
            static function (QueryData $arg) use ($publicLinkData) {
                $params = $arg->getQuery()->getBindValues();

                return $params['id'] === $publicLinkData->getId()
                       && $params['hash'] === $publicLinkData->getHash()
                       && $params['data'] === $publicLinkData->getData()
                       && $params['dateExpire'] === $publicLinkData->getDateExpire()
                       && $params['countViews'] === 0
                       && $params['maxCountViews'] === $publicLinkData->getMaxCountViews()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLinkRepository->refresh($publicLinkData);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
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

        $this->publicLinkRepository->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database->expects(self::never())
                       ->method('doQuery');

        $this->assertEquals(0, $this->publicLinkRepository->deleteByIdBatch([]));
    }

    public function testGetByHash(): void
    {
        $hash = self::$faker->sha1;

        $callback = new Callback(
            static function (QueryData $arg) use ($hash) {
                return $arg->getQuery()->getBindValues()['hash'] === $hash
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doSelect')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLinkRepository->getByHash($hash);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate(): void
    {
        $publicLinkData = $this->buildPublicLinkData();

        $callback = new Callback(
            static function (QueryData $arg) use ($publicLinkData) {
                $params = $arg->getQuery()->getBindValues();

                return $params['itemId'] === $publicLinkData->getItemId()
                       && $params['hash'] === $publicLinkData->getHash()
                       && $params['data'] === $publicLinkData->getData()
                       && $params['userId'] === $publicLinkData->getUserId()
                       && $params['typeId'] === $publicLinkData->getTypeId()
                       && $params['notify'] === (int)$publicLinkData->isNotify()
                       && $params['dateExpire'] === $publicLinkData->getDateExpire()
                       && $params['maxCountViews'] === $publicLinkData->getMaxCountViews()
                       && $params['useInfo'] === $publicLinkData->getUseInfo()
                       && $params['id'] === $publicLinkData->getId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLinkRepository->update($publicLinkData);
    }

    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();

                return count($query->getBindValues()) === 0
                       && $arg->getMapClassName() === Simple::class
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->publicLinkRepository->search(new ItemSearchData());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->publicLinkRepository = new PublicLinkRepository(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
