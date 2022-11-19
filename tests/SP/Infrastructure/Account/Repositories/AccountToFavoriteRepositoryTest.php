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
use SP\Domain\Common\Out\SimpleModel;
use SP\Infrastructure\Account\Repositories\AccountToFavoriteRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountToFavoriteRepositoryTest
 */
class AccountToFavoriteRepositoryTest extends UnitaryTestCase
{
    private MockObject|DatabaseInterface $databaseInterface;
    private AccountToFavoriteRepository  $accountToFavoriteRepository;

    public function testGetForUserId()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['userId'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountToFavoriteRepository->getForUserId($id);
    }

    public function testDelete()
    {
        $accountId = self::$faker->randomNumber();
        $userId = self::$faker->randomNumber();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountId, $userId) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return $params['accountId'] === $accountId
                       && $params['userId'] === $userId
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountToFavoriteRepository->delete($accountId, $userId));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAdd()
    {
        $accountId = self::$faker->randomNumber();
        $userId = self::$faker->randomNumber();

        $expected = new QueryResult();
        $expected->setLastId(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountId, $userId) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return $params['accountId'] === $accountId
                       && $params['userId'] === $userId
                       && !empty($query->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getLastId(), $this->accountToFavoriteRepository->add($accountId, $userId));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseInterface = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->accountToFavoriteRepository = new AccountToFavoriteRepository(
            $this->databaseInterface,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
