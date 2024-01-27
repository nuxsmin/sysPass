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

namespace SPT\Infrastructure\Auth\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Auth\Repositories\AuthToken;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\AuthTokenGenerator;
use SPT\UnitaryTestCase;

/**
 * Class AuthTokenRepositoryTest
 *
 * @group unitary
 */
class AuthTokenTest extends UnitaryTestCase
{

    private AuthToken $authToken;
    private MockObject|DatabaseInterface $database;

    /**
     * @throws ConstraintException
     * @throws QueryException
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
                       && $params['userLogin'] === $searchStringLike
                       && $params['userName'] === $searchStringLike
                       && $arg->getMapClassName() === Simple::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback, true);

        $this->authToken->search($item);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return count($query->getBindValues()) === 0
                       && $arg->getMapClassName() === Simple::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback, true);

        $this->authToken->search(new ItemSearchData());
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

        $this->authToken->deleteByIdBatch($ids);
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

        $this->authToken->deleteByIdBatch([]);
    }

    public function testGetTokenByUserId()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['userId'] === $id
                       && $arg->getMapClassName() === AuthTokenModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->authToken->getTokenByUserId($id);
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
                       && $arg->getMapClassName() === AuthTokenModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->authToken->getById($id);
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($authToken) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['id'] === $authToken->getId()
                       && $params['userId'] === $authToken->getUserId()
                       && $params['token'] === $authToken->getToken()
                       && $params['actionId'] === $authToken->getActionId()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($authToken) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 7
                       && $params['id'] === $authToken->getId()
                       && $params['userId'] === $authToken->getUserId()
                       && $params['token'] === $authToken->getToken()
                       && $params['createdBy'] === $authToken->getCreatedBy()
                       && $params['actionId'] === $authToken->getActionId()
                       && $params['hash'] === $authToken->getHash()
                       && $params['vault'] === $authToken->getVault()
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('doQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackUpdate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->authToken->update($authToken);
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdateWithDuplicate()
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $callback = new Callback(
            static function (QueryData $arg) use ($authToken) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 4
                       && $params['id'] === $authToken->getId()
                       && $params['userId'] === $authToken->getUserId()
                       && $params['token'] === $authToken->getToken()
                       && $params['actionId'] === $authToken->getActionId()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Authorization already exist');

        $this->authToken->update($authToken);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRefreshVaultByUserId()
    {
        $userId = self::$faker->randomNumber();
        $vault = self::$faker->sha1();
        $hash = self::$faker->sha1();

        $callback = new Callback(
            static function (QueryData $arg) use ($userId, $vault, $hash) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['userId'] === $userId
                       && $params['vault'] === $vault
                       && $params['hash'] === $hash
                       && $arg->getOnErrorMessage() === 'Internal error'
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $queryResult = new QueryResult([1]);

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($queryResult->setAffectedNumRows(10));

        $out = $this->authToken->refreshVaultByUserId($userId, $vault, $hash);

        self::assertEquals(10, $out);
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

        $this->authToken->delete($id);
    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                return $arg->getMapClassName() === AuthTokenModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->authToken->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRefreshTokenByUserId()
    {
        $userId = self::$faker->randomNumber();
        $token = self::$faker->sha1();

        $callback = new Callback(
            static function (QueryData $arg) use ($userId, $token) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['userId'] === $userId
                       && $params['token'] === $token
                       && $arg->getOnErrorMessage() === 'Internal error'
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([1]));

        $this->authToken->refreshTokenByUserId($userId, $token);
    }

    public function testGetTokenByToken()
    {
        $actionId = self::$faker->randomNumber();
        $token = self::$faker->sha1();

        $callback = new Callback(
            static function (QueryData $arg) use ($actionId, $token) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['actionId'] === $actionId
                       && $params['token'] === $token
                       && $arg->getMapClassName() === AuthTokenModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback);

        $this->authToken->getTokenByToken($actionId, $token);
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testCreate()
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $callbackDuplicate = new Callback(
            static function (QueryData $arg) use ($authToken) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['userId'] === $authToken->getUserId()
                       && $params['token'] === $authToken->getToken()
                       && $params['actionId'] === $authToken->getActionId()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($authToken) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 6
                       && $params['userId'] === $authToken->getUserId()
                       && $params['token'] === $authToken->getToken()
                       && $params['createdBy'] === $authToken->getCreatedBy()
                       && $params['actionId'] === $authToken->getActionId()
                       && $params['hash'] === $authToken->getHash()
                       && $params['vault'] === $authToken->getVault()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(2))
            ->method('doQuery')
            ->with(...self::withConsecutive([$callbackDuplicate], [$callbackUpdate]))
            ->willReturn(new QueryResult([]), new QueryResult([1]));

        $this->authToken->create($authToken);
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testCreateWithDuplicate()
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $callback = new Callback(
            static function (QueryData $arg) use ($authToken) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['userId'] === $authToken->getUserId()
                       && $params['token'] === $authToken->getToken()
                       && $params['actionId'] === $authToken->getActionId()
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult([1]));

        $this->expectException(DuplicatedItemException::class);
        $this->expectExceptionMessage('Authorization already exist');

        $this->authToken->create($authToken);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->authToken = new AuthToken(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
