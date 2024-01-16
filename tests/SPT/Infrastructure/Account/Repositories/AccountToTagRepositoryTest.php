<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Infrastructure\Account\Repositories;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Account\Repositories\AccountToTag;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class AccountToTagRepositoryTest
 *
 * @group unitary
 */
class AccountToTagRepositoryTest extends UnitaryTestCase
{
    private MockObject|DatabaseInterface $database;
    private AccountToTag $accountToTag;

    public function testGetTagsByAccountId(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $query = $arg->getQuery();

                return $query->getBindValues()['accountId'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountToTag->getTagsByAccountId($id);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
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

        $this->assertTrue($this->accountToTag->deleteByAccountId($accountId));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd(): void
    {
        $id = self::$faker->randomNumber();
        $tags = self::getRandomNumbers(10);

        $callbacks = array_map(
            static function ($tag) use ($id) {
                return [
                    new Callback(
                        static function (QueryData $arg) use ($id, $tag) {
                            $query = $arg->getQuery();
                            $params = $query->getBindValues();

                            return $params['accountId'] === $id
                                   && $params['tagId'] === $tag
                                   && !empty($query->getStatement());
                        }
                    ),
                ];
            },
            $tags
        );

        $this->database
            ->expects(self::exactly(count($tags)))
            ->method('doQuery')
            ->with(...self::withConsecutive(...$callbacks));

        $this->accountToTag->add($id, $tags);
    }

    /**
     * @throws ContextException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->accountToTag = new AccountToTag(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
