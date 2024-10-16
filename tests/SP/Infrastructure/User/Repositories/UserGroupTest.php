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

namespace SP\Tests\Infrastructure\User\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Models\Simple as SimpleModel;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\User\Models\UserGroup as UserGroupModel;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserGroup;
use SP\Tests\Generators\UserGroupGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class UserGroupTest
 */
#[Group('unitary')]
class UserGroupTest extends UnitaryTestCase
{

    private UserGroup                    $userGroup;
    private MockObject|DatabaseInterface $database;

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testCreate()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($userGroup) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['name'] === $userGroup->getName()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($userGroup) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['name'] === $userGroup->getName()
                       && $params['description'] === $userGroup->getDescription()
                       && $params['users'] === $userGroup->getUsers()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('runQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackCreate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->userGroup->create($userGroup);
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testCreateWithDuplicate()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($userGroup) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['name'] === $userGroup->getName()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackDuplicate)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Duplicated group name');

        $this->userGroup->create($userGroup);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($userGroup) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['id'] === $userGroup->getId()
                       && $params['name'] === $userGroup->getName()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($userGroup) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['id'] === $userGroup->getId()
                       && $params['name'] === $userGroup->getName()
                       && $params['description'] === $userGroup->getDescription()
                       && $params['users'] === $userGroup->getUsers()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('runQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackUpdate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->userGroup->update($userGroup);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateWithDuplicate()
    {
        $userGroup = UserGroupGenerator::factory()->buildUserGroupData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($userGroup) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['id'] === $userGroup->getId()
                       && $params['name'] === $userGroup->getName()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackDuplicate)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Duplicated group name');

        $this->userGroup->update($userGroup);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageByUsers()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['userGroupId'] === 100;
                })
            );

        $this->userGroup->getUsageByUsers(100);
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

        $this->userGroup->delete($id);
    }

    public function testSearch()
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 2
                       && $params['name'] === $searchStringLike
                       && $params['description'] === $searchStringLike
                       && $arg->getMapClassName() === UserGroupModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->userGroup->search($item);
    }

    public function testSearchWithNoString()
    {
        $item = new ItemSearchDto();

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 0
                       && $arg->getMapClassName() === UserGroupModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->userGroup->search($item);
    }

    public function testGetById()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['id'] === 100
                           && $queryData->getMapClassName() === UserGroupModel::class;
                })
            );

        $this->userGroup->getById(100);
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === UserGroupModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->userGroup->getAll();
    }

    public function testGetUsage()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['userGroupId'] === 100;
                })
            );

        $this->userGroup->getUsage(100);
    }

    public function testGetByName()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['name'] === 'a_name'
                           && $queryData->getMapClassName() === UserGroupModel::class;
                })
            );

        $this->userGroup->getByName('a_name');
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
                       && $arg->getMapClassName() === SimpleModel::class
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->userGroup->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database
            ->expects(self::never())
            ->method('runQuery');

        $this->userGroup->deleteByIdBatch([]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->userGroup = new UserGroup(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
