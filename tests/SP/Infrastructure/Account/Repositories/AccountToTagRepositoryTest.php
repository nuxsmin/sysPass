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
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Common\Adapters\SimpleModel;
use SP\Infrastructure\Account\Repositories\AccountToTagRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountToTagRepositoryTest
 *
 * @group unitary
 */
class AccountToTagRepositoryTest extends UnitaryTestCase
{
    private MockObject|DatabaseInterface $database;
    private AccountToTagRepository       $accountToTagRepository;

    public function testGetTagsByAccountId(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['accountId'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountToTagRepository->getTagsByAccountId($id);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDeleteByAccountId(): void
    {
        $accountId = self::$faker->randomNumber();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

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
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountToTagRepository->deleteByAccountId($accountId));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testAdd(): void
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = self::$faker->randomNumber();
        $accountRequest->tags = self::getRandomNumbers(10);

        $callbacks = array_map(
            static function ($tag) use ($accountRequest) {
                return [
                    new Callback(
                        static function (QueryData $arg) use ($accountRequest, $tag) {
                            $query = $arg->getQuery();
                            $params = $query->getBindValues();

                            return $params['accountId'] === $accountRequest->id
                                   && $params['tagId'] === $tag
                                   && !empty($query->getStatement());
                        }
                    ),
                ];
            },
            $accountRequest->tags
        );

        $this->database
            ->expects(self::exactly(count($accountRequest->tags)))
            ->method('doQuery')
            ->withConsecutive(...$callbacks);

        $this->accountToTagRepository->add($accountRequest);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->accountToTagRepository = new AccountToTagRepository(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
