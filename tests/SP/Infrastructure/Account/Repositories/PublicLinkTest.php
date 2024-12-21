<?php
declare(strict_types=1);
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

namespace SP\Tests\Infrastructure\Account\Repositories;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Infrastructure\Account\Repositories\PublicLink;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class PublicLinkRepositoryTest
 *
 */
#[Group('unitary')]
class PublicLinkTest extends UnitaryTestCase
{

    private PublicLink $publicLink;
    private MockObject|DatabaseInterface $database;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDelete(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getQuery()->getBindValues()['id'] === 100
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('runQuery')
                       ->with($callback)
            ->willReturn(new QueryResult(null, 1));

        $this->publicLink->delete(100);
    }

    public function testSearch(): void
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $params = $arg->getQuery()->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return $params['login'] === $searchStringLike
                       && $params['accountName'] === $searchStringLike
                       && $params['clientName'] === $searchStringLike
                       && $arg->getMapClassName() === PublicLinkModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->publicLink->search($item);
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
            ->method('runQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLink->getHashForItem($itemId);
    }

    public function testGetById(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === PublicLinkModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('runQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLink->getById($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAddLinkView(): void
    {
        $publicLinkData = $this->buildPublicLinkData();

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
            ->method('runQuery')
                       ->with($callback)
            ->willReturn(new QueryResult(null, 1));

        $this->assertTrue($this->publicLink->addLinkView($publicLinkData));
    }

    private function buildPublicLinkData(): PublicLinkModel
    {
        $data = [
            'id' => self::$faker->randomNumber(),
            'itemId' => self::$faker->randomNumber(),
            'hash' => self::$faker->sha1,
            'userId' => self::$faker->randomNumber(),
            'typeId' => self::$faker->randomNumber(),
            'notify' => self::$faker->boolean,
            'dateAdd' => self::$faker->unixTime,
            'dateUpdate' => self::$faker->unixTime,
            'dateExpire' => self::$faker->unixTime,
            'countViews' => self::$faker->randomNumber(),
            'totalCountViews' => self::$faker->randomNumber(),
            'maxCountViews' => self::$faker->randomNumber(),
            'useInfo' => self::$faker->text(),
            'data' => self::$faker->text(),
        ];

        return new PublicLinkModel($data);
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
            ->method('runQuery')
                       ->with(...self::withConsecutive([$callbackCheckDuplicate], [$callbackCreate]))
                       ->willReturn(new QueryResult());

        $this->publicLink->create($publicLinkData);
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
            ->method('runQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Link already created');

        $this->publicLink->create($publicLinkData);
    }

    public function testGetAll(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === PublicLinkModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->publicLink->getAll();
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
            ->method('runQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLink->refresh($publicLinkData);
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
            ->method('runQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLink->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database->expects(self::never())
            ->method('runQuery');

        $this->assertEquals(0, $this->publicLink->deleteByIdBatch([]));
    }

    public function testGetByHash(): void
    {
        $hash = self::$faker->sha1;

        $callback = new Callback(
            static function (QueryData $arg) use ($hash) {
                return $arg->getQuery()->getBindValues()['hash'] === $hash
                       && $arg->getMapClassName() === PublicLinkModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('runQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLink->getByHash($hash);
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
            ->method('runQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->publicLink->update($publicLinkData);
    }

    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();

                return count($query->getBindValues()) === 0
                       && $arg->getMapClassName() === PublicLinkModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->publicLink->search(new ItemSearchDto());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->publicLink = new PublicLink(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
