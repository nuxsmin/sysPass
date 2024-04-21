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

namespace SPT\Infrastructure\User\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserToUserGroup;
use SPT\UnitaryTestCase;

/**
 * Class UserToUserGroupTest
 */
#[Group('unitary')]
class UserToUserGroupTest extends UnitaryTestCase
{

    private UserToUserGroup              $userToUserGroup;
    private MockObject|DatabaseInterface $database;

    public function testGetById()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['userGroupId'] === 100
                           && $queryData->getMapClassName() === UserToUserGroupModel::class;
                })
            );

        $this->userToUserGroup->getById(100);
    }

    public function testGetGroupsForUser()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['userId'] === 100
                           && $queryData->getMapClassName() === UserToUserGroupModel::class;
                })
            );

        $this->userToUserGroup->getGroupsForUser(100);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $callbackCreate = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 6
                       && array_shift($params) === 100
                       && array_shift($params) === 201
                       && array_shift($params) === 100
                       && array_shift($params) === 202
                       && array_shift($params) === 100
                       && array_shift($params) === 203
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->userToUserGroup->add(100, [201, 202, 203]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAddWithNoUsers()
    {
        $this->database
            ->expects(self::never())
            ->method('runQuery');

        $this->userToUserGroup->add(100, []);
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
                $values = $query->getBindValues();

                return count($values) === 1
                       && $values['userGroupId'] === $id
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->userToUserGroup->delete($id);
    }

    public function testCheckUserInGroup()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 2
                           && $params['userGroupId'] === 100
                           && $params['userId'] === 200;
                })
            )
            ->willReturn(new QueryResult([1]));

        $this->assertTrue($this->userToUserGroup->checkUserInGroup(100, 200));
    }

    public function testCheckUserInGroupWithNoResults()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 2
                           && $params['userGroupId'] === 100
                           && $params['userId'] === 200;
                })
            )
            ->willReturn(new QueryResult([]));

        $this->assertFalse($this->userToUserGroup->checkUserInGroup(100, 200));
    }

    /**
     * @throws ServiceException
     */
    public function testUpdate()
    {
        $this->database
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->database
            ->expects($this->once())
            ->method('endTransaction')
            ->willReturn(true);

        $callbackDelete = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $values = $query->getBindValues();

                return count($values) === 1
                       && $values['userGroupId'] === 100
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackCreate = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 6
                       && array_shift($params) === 100
                       && array_shift($params) === 201
                       && array_shift($params) === 100
                       && array_shift($params) === 202
                       && array_shift($params) === 100
                       && array_shift($params) === 203
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $queryResult = new QueryResult([1]);

        $this->database
            ->expects(self::exactly(2))
            ->method('runQuery')
            ->with(...self::withConsecutive([$callbackDelete], [$callbackCreate]))
            ->willReturn($queryResult);

        $out = $this->userToUserGroup->update(100, [201, 202, 203]);

        $this->assertEquals($queryResult, $out);
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateWithNoUsers()
    {
        $this->database
            ->expects(self::never())
            ->method('runQuery');

        $this->userToUserGroup->update(100, []);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->userToUserGroup = new UserToUserGroup(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
