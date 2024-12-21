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
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Models\Simple as SimpleModel;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\User as UserModel;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\User;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class UserTest
 */
#[Group('unitary')]
class UserTest extends UnitaryTestCase
{

    private User                         $user;
    private MockObject|DatabaseInterface $database;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageForUser()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['userId'] === 100;
                })
            );

        $this->user->getUsageForUser(100);
    }

    /**
     * @throws SPException
     */
    public function testCreate()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['login'] === $user->getLogin()
                       && $params['email'] === $user->getEmail()
                       && $params['ssoLogin'] === $user->getSsoLogin()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 22
                       && count(array_diff_assoc($params, $user->toArray(null, ['id', 'hashSalt']))) === 0
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('runQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackCreate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->user->create($user);
    }

    /**
     * @throws SPException
     */
    public function testCreateWithDuplicate()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['login'] === $user->getLogin()
                       && $params['email'] === $user->getEmail()
                       && $params['ssoLogin'] === $user->getSsoLogin()
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
        $this->expectExceptionMessage('Duplicated user login/email');

        $this->user->create($user);
    }

    public function testGetUserEmail()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 0
                           && $queryData->getMapClassName() === UserModel::class;
                })
            );

        $this->user->getUserEmail();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateOnLogin()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                $count = count(array_diff_assoc($params, $user->toArray(['pass', 'name', 'email', 'isLdap', 'login'])));

                return count($params) === 5
                       && $count === 0
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackUpdate)
            ->willReturn(new QueryResult(null, 1));

        $out = $this->user->updateOnLogin($user);

        $this->assertEquals(1, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateMasterPassById()
    {
        $callbackUpdate = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['pass'] === 'super_secret'
                       && $params['key'] === 'a_key'
                       && $params['id'] === 100
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackUpdate)
            ->willReturn(new QueryResult(null, 1));

        $out = $this->user->updateMasterPassById(100, 'super_secret', 'a_key');

        $this->assertEquals(1, $out);
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

        $this->user->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
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
                           && $queryData->getMapClassName() === UserModel::class;
                })
            );

        $this->user->getById(100);
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

        $this->user->deleteByIdBatch($ids);
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

        $this->user->deleteByIdBatch([]);
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === UserModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->user->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateLastLoginById()
    {
        $callbackUpdate = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['id'] === 100
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackUpdate)
            ->willReturn(new QueryResult(null, 1));

        $out = $this->user->updateLastLoginById(100);

        $this->assertEquals(1, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByLogin()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['login'] === 'a_login'
                           && $queryData->getMapClassName() === UserModel::class;
                })
            );

        $this->user->getByLogin('a_login');
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['id'] === $user->getId()
                       && $params['login'] === $user->getLogin()
                       && $params['email'] === $user->getEmail()
                       && $params['ssoLogin'] === $user->getSsoLogin()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 23
                       && count(array_diff_assoc($params, $user->toArray(null, ['hashSalt']))) === 0
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('runQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackUpdate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->user->update($user);
    }

    /**
     * @throws SPException
     */
    public function testUpdateWithDuplicate()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['id'] === $user->getId()
                       && $params['login'] === $user->getLogin()
                       && $params['email'] === $user->getEmail()
                       && $params['ssoLogin'] === $user->getSsoLogin()
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
        $this->expectExceptionMessage('Duplicated user login/email');

        $this->user->update($user);
    }

    public function testGetUserEmailById()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 2
                           && array_shift($params) === 100
                           && array_shift($params) === 200
                           && $queryData->getMapClassName() === UserModel::class;
                })
            );

        $this->user->getUserEmailById([100, 200]);
    }

    public function testCheckExistsByLogin()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['login'] === 'a_login'
                           && $queryData->getMapClassName() === SimpleModel::class;
                })
            )
            ->willReturn(new QueryResult([1]));

        $out = $this->user->checkExistsByLogin('a_login');

        $this->assertTrue($out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUserEmailForGroup()
    {
        $this->database
            ->expects($this->once())
            ->method('runQuery')
            ->with(
                self::callback(static function (QueryData $queryData) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 1
                           && $params['userGroupId'] === 100
                           && $queryData->getMapClassName() === UserModel::class;
                })
            );

        $this->user->getUserEmailForGroup(100);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
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
                       && $params['login'] === $searchStringLike
                       && $arg->getMapClassName() === UserModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->user->search($item);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearchWithNoString()
    {
        $item = new ItemSearchDto();

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 0
                       && $arg->getMapClassName() === UserModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->user->search($item);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testSearchWithAdmin()
    {
        $this->context->setUserData(
            UserDto::fromModel(
                UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(
                                     [
                                         'isAdminApp' => true,
                                     ]
                                 )
            )
        );

        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 2
                       && $params['name'] === $searchStringLike
                       && $params['login'] === $searchStringLike
                       && $arg->getMapClassName() === UserModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->user->search($item);
    }

    /**
     * @throws ConstraintException
     * @throws JsonException
     * @throws QueryException
     */
    public function testUpdatePreferencesById()
    {
        $userPreferences = UserDataGenerator::factory()->buildUserPreferencesData();

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($userPreferences) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['id'] === 100
                       && $params['preferences'] === $userPreferences->toJson()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackUpdate)
            ->willReturn(new QueryResult(null, 1));

        $this->user->updatePreferencesById(100, $userPreferences);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdatePassById()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($user) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 5
                       && $params['id'] === $user->getId()
                       && $params['pass'] === $user->getPass()
                       && $params['isChangePass'] === $user->isChangePass()
                       && $params['isChangedPass'] === $user->isChangedPass()
                       && $params['isMigrate'] === $user->isMigrate()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callbackUpdate)
            ->willReturn(new QueryResult(null, 1));

        $this->user->updatePassById($user);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->user = new User(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
