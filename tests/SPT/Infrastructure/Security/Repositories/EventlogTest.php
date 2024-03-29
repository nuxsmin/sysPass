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

namespace SPT\Infrastructure\Security\Repositories;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Security\Models\Eventlog as EventlogModel;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Security\Repositories\Eventlog;
use SPT\Generators\EventlogGenerator;
use SPT\UnitaryTestCase;

/**
 * Class EventlogTest
 */
#[Group('unitary')]
class EventlogTest extends UnitaryTestCase
{

    private Eventlog                     $eventlog;
    private DatabaseInterface|MockObject $database;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testClear()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();

                return count($query->getBindValues()) === 0
                       && is_a($query, DeleteInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->eventlog->clear();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $eventlog = EventlogGenerator::factory()->buildEventlog();

        $callbackCreate = new Callback(
            static function (QueryData $arg) use ($eventlog) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 6
                       && $params['description'] === $eventlog->getDescription()
                       && $params['login'] === $eventlog->getLogin()
                       && $params['action'] === $eventlog->getAction()
                       && $params['userId'] === $eventlog->getUserId()
                       && $params['ipAddress'] === $eventlog->getIpAddress()
                       && $params['level'] === $eventlog->getLevel()
                       && is_a($query, InsertInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::exactly(1))
            ->method('runQuery')
            ->with($callbackCreate)
            ->willReturn(new QueryResult([]));

        $this->eventlog->create($eventlog);
    }

    public function testSearch()
    {
        $item = new ItemSearchData(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 3
                       && $params['action'] === $searchStringLike
                       && $params['ipAddress'] === $searchStringLike
                       && $params['description'] === $searchStringLike
                       && $arg->getMapClassName() === EventlogModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->eventlog->search($item);
    }

    public function testSearchWithEmptyString()
    {
        $item = new ItemSearchData();

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 0
                       && $arg->getMapClassName() === EventlogModel::class
                       && is_a($query, SelectInterface::class)
                       && !empty($query->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('runQuery')
            ->with($callback, true);

        $this->eventlog->search($item);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $this->eventlog = new Eventlog(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
        );
    }
}
