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

use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\UserPassRecover as UserPassRecoverModel;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserPassRecover;
use SPT\UnitaryTestCase;

/**
 * Class UserPassRecoverTest
 */
#[Group('unitary')]
class UserPassRecoverTest extends UnitaryTestCase
{

    private UserPassRecover              $userPassRecover;
    private MockObject|DatabaseInterface $database;

    public function testGetAttemptsByUserId()
    {
        $time = self::$faker->unixTime();

        $this->database
            ->expects($this->once())
            ->method('doSelect')
            ->with(
                self::callback(static function (QueryData $queryData) use ($time) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 2
                           && $params['userId'] === 100
                           && $params['date'] === $time
                           && $queryData->getMapClassName() === Simple::class;
                })
            );

        $this->userPassRecover->getAttemptsByUserId(100, $time);
    }

    public function testGetUserIdForHash()
    {
        $time = self::$faker->unixTime();
        $hash = self::$faker->sha1();

        $this->database
            ->expects($this->once())
            ->method('doSelect')
            ->with(
                self::callback(static function (QueryData $queryData) use ($hash, $time) {
                    $params = $queryData->getQuery()->getBindValues();

                    return count($params) === 2
                           && $params['hash'] === $hash
                           && $params['date'] === $time
                           && $queryData->getMapClassName() === UserPassRecoverModel::class;
                })
            );

        $this->userPassRecover->getUserIdForHash($hash, $time);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $hash = self::$faker->sha1();

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($hash) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 2
                       && $params['userId'] === 100
                       && $params['hash'] === $hash
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult([1]));

        $this->userPassRecover->add(100, $hash);
    }

    /**
     * @throws SPException
     */
    public function testToggleUsedByHash()
    {
        $time = self::$faker->unixTime();
        $hash = self::$faker->sha1();

        $callbackUpdate = new Callback(
            static function (QueryData $arg) use ($hash, $time) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 3
                       && $params['hash'] === $hash
                       && $params['date'] === $time
                       && $params['used'] === 1
                       && is_a($query, UpdateInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $queryResult = new QueryResult();

        $this->database
            ->expects(self::once())
            ->method('doQuery')
            ->with($callbackUpdate)
            ->willReturn($queryResult->setAffectedNumRows(1));

        $out = $this->userPassRecover->toggleUsedByHash($hash, $time);

        self::assertEquals(1, $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->userPassRecover = new UserPassRecover(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
