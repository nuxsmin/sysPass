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

namespace SP\Tests\Infrastructure\Notification\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Notification\Repositories\Notification;
use SP\Tests\Generators\NotificationDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class NotificationTest
 *
 */
#[Group('unitary')]
class NotificationTest extends UnitaryTestCase
{

    private Notification $notification;

    public function testGetByIdBatch()
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
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->notification->getByIdBatch($ids);
    }

    public function testGetAllActiveForAdmin()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['userId'] === 100
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->notification->getAllActiveForAdmin(100);
    }

    public function testGetAllForUserId()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['userId'] === 100
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->notification->getAllForUserId(100);
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

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->notification->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $notification = NotificationDataGenerator::factory()->buildNotification();

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($notification) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 9
                       && $params['id'] === $notification->getId()
                       && $params['type'] === $notification->getType()
                       && $params['userId'] === $notification->getUserId()
                       && $params['component'] === $notification->getComponent()
                       && $params['description'] === $notification->getDescription()
                       && $params['date'] === $notification->getDate()
                       && $params['checked'] === $notification->isChecked()
                       && $params['sticky'] === $notification->isSticky()
                       && $params['onlyAdmin'] === $notification->isOnlyAdmin()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult(null, 1));

        $out = $this->notification->update($notification);

        $this->assertEquals(1, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSetCheckedById()
    {
        $callbackCreate = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['id'] === 100
                       && $params['checked'] === 1
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult(null, 1));

        $out = $this->notification->setCheckedById(100);

        $this->assertEquals(1, $out);
    }

    public function testGetAllActiveForUserId()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['userId'] === 100
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->notification->getAllForUserId(100);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteAdminBatch()
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
            ->method('runQuery')
            ->with($callback);

        $this->notification->deleteAdminBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $notification = NotificationDataGenerator::factory()->buildNotification();

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($notification) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 7
                       && $params['type'] === $notification->getType()
                       && $params['userId'] === $notification->getUserId()
                       && $params['component'] === $notification->getComponent()
                       && $params['description'] === $notification->getDescription()
                       && $params['checked'] === $notification->isChecked()
                       && $params['sticky'] === $notification->isSticky()
                       && $params['onlyAdmin'] === $notification->isOnlyAdmin()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult([]));

        $this->notification->create($notification);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteAdmin()
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

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->notification->deleteAdmin($id);
    }

    public function testGetById()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['id'] === 100
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->notification->getById(100);
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
            ->method('runQuery')
            ->with($callback);

        $this->notification->deleteByIdBatch($ids);
    }

    public function testSearchForUserId()
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 3
                       && $params['userId'] === 100
                       && $params['type'] === $searchStringLike
                       && $params['component'] === $searchStringLike
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->notification->searchForUserId($item, 100);
    }

    public function testSearchForAdmin()
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 3
                       && $params['userId'] === 100
                       && $params['type'] === $searchStringLike
                       && $params['component'] === $searchStringLike
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->notification->searchForAdmin($item, 100);
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();

                return $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->notification->getAll();
    }

    public function testGetForUserIdByDate()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['userId'] === 100
                       && $params['component'] === 'test'
                       && $arg->getMapClassName() === NotificationModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->notification->getForUserIdByDate('test', 100);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->notification = new Notification(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
