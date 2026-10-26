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
use SP\Domain\Common\Models\Item;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Infrastructure\Account\Repositories\AccountToUser;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountToUserRepositoryTest
 *
 */
#[Group('unitary')]
class AccountToUserTest extends UnitaryTestCase
{
    private MockObject|DatabaseInterface $database;
    private AccountToUser $accountToUser;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDeleteTypeByAccountId(): void
    {
        $accountId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($accountId) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return $params['accountId'] === $accountId
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult(null, 1));

        $this->assertTrue($this->accountToUser->deleteByAccountId($accountId));
    }

    public function testGetUserGroupsByAccountId(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['accountId'] === $id
                       && $arg->getMapClassName() === Item::class
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountToUser->getUsersByAccountId($id);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDeleteByUserGroupId(): void
    {
        $accountId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($accountId) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return $params['accountId'] === $accountId
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult(null, 1));

        $this->assertTrue($this->accountToUser->deleteByAccountId($accountId));
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testAddByType(): void
    {
        $userGroups = self::getRandomNumbers(10);

        $callback = new Callback(
            static fn(QueryData $arg) => !empty($arg->getQuery()->getStatement())
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback);

        $this->accountToUser->addByType(
            self::$faker->randomNumber(),
            $userGroups,
            self::$faker->boolean
        );
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDeleteByAccountId(): void
    {
        $accountId = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($accountId) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return $params['accountId'] === $accountId
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback)
            ->willReturn(new QueryResult(null, 1));

        $this->assertTrue($this->accountToUser->deleteByAccountId($accountId));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->accountToUser = new AccountToUser(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
