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

namespace SPT\Infrastructure\Database;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use RuntimeException;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Database\Ports\QueryDataInterface;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Database\DbStorageDriver;
use SPT\UnitaryTestCase;

/**
 * Class DatabaseTest
 */
#[Group('unitary')]
class DatabaseTest extends UnitaryTestCase
{

    private MockObject|DbStorageHandler $dbStorageHandler;
    private Database                    $database;

    public static function bufferedDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @throws Exception
     */
    public function testBeginTransaction()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(false);

        $pdo->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        self::assertTrue($this->database->beginTransaction());
    }

    /**
     * @throws Exception
     */
    public function testBeginTransactionWithExistingTransaction()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $pdo->expects($this->never())
            ->method('beginTransaction');

        self::assertTrue($this->database->beginTransaction());
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRunQueryWithMappedClass()
    {
        list($pdoStatement, $query) = $this->checkPrepare();

        $pdoStatement->expects($this->once())
                     ->method('fetchAll')
                     ->with(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Simple::class);

        $queryData = $this->createMock(QueryDataInterface::class);

        $queryData->expects($this->once(1))
                  ->method('getQuery')
                  ->willReturn($query);

        $queryData->expects($this->once(1))
                  ->method('getMapClassName')
                  ->willReturn(Simple::class);

        $this->database->runQuery($queryData);
    }

    /**
     * @param string $queryType
     * @param bool $useValues
     * @param int $times
     * @param array $prepareOptions
     * @return array
     * @throws Exception
     */
    private function checkPrepare(
        string $queryType = SelectInterface::class,
        bool   $useValues = true,
        int    $times = 1,
        array  $prepareOptions = []
    ): array {
        $pdo = $this->createMock(PDO::class);
        $pdoStatement = $this->createMock(PDOStatement::class);
        $query = $this->createMock($queryType);

        $query->expects($this->atLeast($times))
              ->method('getStatement')
              ->willReturn('test_query');

        if ($useValues) {
            $query->expects($this->exactly($times))
                  ->method('getBindValues')
                  ->willReturn(['a' => 'test', 'b' => 100, 'c' => false]);

            $counter = new InvokedCount(3 * $times);
            $pdoStatement->expects($counter)
                         ->method('bindValue')
                         ->with(
                             self::callback(static function (string $arg) use ($counter) {
                                 return match ($counter->numberOfInvocations()) {
                                     1, 4 => $arg === 'a',
                                     2, 5 => $arg === 'b',
                                     3, 6 => $arg === 'c',
                                 };
                             }),
                             self::callback(static function (mixed $arg) use ($counter) {
                                 return match ($counter->numberOfInvocations()) {
                                     1, 4 => $arg === 'test',
                                     2, 5 => $arg === 100,
                                     3, 6 => $arg === false,
                                 };
                             }),
                             self::callback(static function (int $arg) use ($counter) {
                                 return match ($counter->numberOfInvocations()) {
                                     1, 4 => $arg === PDO::PARAM_STR,
                                     2, 5 => $arg === PDO::PARAM_INT,
                                     3, 6 => $arg === PDO::PARAM_BOOL,
                                 };
                             }),
                         );
        } else {
            $query->expects($this->exactly($times))
                  ->method('getBindValues')
                  ->willReturn([]);

            $pdoStatement->expects($this->never())
                         ->method('bindValue');
        }

        $pdo->expects($this->exactly($times))
            ->method('prepare')
            ->with('test_query', $prepareOptions)
            ->willReturn($pdoStatement);

        $pdoStatement->expects($this->exactly($times))
                     ->method('execute');

        $this->dbStorageHandler
            ->expects($this->exactly($times))
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->exactly($times))
            ->method('lastInsertId')
            ->willReturn('123');

        return array($pdoStatement, $query);
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRunQueryWithNoMappedClass()
    {
        list($pdoStatement, $query) = $this->checkPrepare();

        $pdoStatement->expects($this->once())
                     ->method('fetchAll')
                     ->with(PDO::FETCH_DEFAULT);

        $queryData = $this->createMock(QueryDataInterface::class);

        $queryData->expects($this->once())
                  ->method('getQuery')
                  ->willReturn($query);

        $queryData->expects($this->once())
                  ->method('getMapClassName');

        $this->database->runQuery($queryData);
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRunQueryWithMappedClassAndFullCount()
    {
        /** @var QueryInterface|MockObject $query */
        /** @var PDO|MockObject $pdoStatement */
        list($pdoStatement, $query) = $this->checkPrepare(times: 2);

        $pdoStatement->expects($this->once())
                     ->method('fetchAll')
                     ->with(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Simple::class);

        $queryData = $this->createMock(QueryDataInterface::class);

        $queryData->expects($this->once())
                  ->method('getQuery')
                  ->willReturn($query);

        $queryData->expects($this->once())
                  ->method('getMapClassName')
                  ->willReturn(Simple::class);

        $queryData->expects($this->once())
                  ->method('getQueryCount')
                  ->willReturn($query);

        $pdoStatement->expects($this->once())
                     ->method('fetchColumn')
                     ->willReturn(10);

        $this->database->runQuery($queryData, true);
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRunQueryWithNoSelect()
    {
        list($pdoStatement, $query) = $this->checkPrepare(QueryInterface::class);

        $queryData = $this->createMock(QueryDataInterface::class);

        $queryData->expects($this->once())
                  ->method('getQuery')
                  ->willReturn($query);

        $queryData->expects($this->never())
                  ->method('getMapClassName');

        $pdoStatement->expects($this->never())
                     ->method('fetchAll');

        $pdoStatement->expects($this->once())
                     ->method('rowCount')
                     ->willReturn(10);

        $out = $this->database->runQuery($queryData);

        $this->assertEquals(10, $out->getAffectedNumRows());
        $this->assertEquals('123', $out->getLastId());
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRunQueryWithNoValues()
    {
        list($pdoStatement, $query) = $this->checkPrepare(QueryInterface::class, false);

        $queryData = $this->createMock(QueryDataInterface::class);

        $queryData->expects($this->once())
                  ->method('getQuery')
                  ->willReturn($query);

        $queryData->expects($this->never())
                  ->method('getMapClassName');

        $pdoStatement->expects($this->never())
                     ->method('fetchAll');

        $pdoStatement->expects($this->once())
                     ->method('rowCount')
                     ->willReturn(10);

        $out = $this->database->runQuery($queryData);

        $this->assertEquals(10, $out->getAffectedNumRows());
        $this->assertEquals('123', $out->getLastId());
    }

    /**
     * @throws Exception
     */
    public function testEndTransaction()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $pdo->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        self::assertTrue($this->database->endTransaction());
    }

    /**
     * @throws Exception
     */
    public function testEndTransactionWithNoExistingTransaction()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(false);

        $pdo->expects($this->never())
            ->method('commit');

        self::assertFalse($this->database->endTransaction());
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    #[DataProvider('bufferedDataProvider')]
    public function testDoFetchWithOptions(bool $buffered)
    {
        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getDriver')
            ->willReturn(DbStorageDriver::mysql);

        /** @var PDOStatement|MockObject $pdoStatement */
        /** @var QueryInterface|MockObject $query */
        list($pdoStatement, $query) = $this->checkPrepare(
            QueryInterface::class,
            false,
            1,
            [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => $buffered]
        );

        $queryData = $this->createMock(QueryDataInterface::class);

        $queryData->expects($this->once())
                  ->method('getQuery')
                  ->willReturn($query);

        $pdoStatement->expects($this->exactly(2))
                     ->method('fetch')
                     ->with(PDO::FETCH_DEFAULT)
                     ->willReturn(['a', 1, false], false);

        $out = $this->database->doFetchWithOptions(queryData: $queryData, buffered: $buffered);

        foreach ($out as $row) {
            $this->assertEquals($row, ['a', 1, false]);
        }
    }

    /**
     * @throws Exception
     */
    public function testRollbackTransaction()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $pdo->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        self::assertTrue($this->database->rollbackTransaction());
    }

    /**
     * @throws Exception
     */
    public function testRollbackTransactionWithNoTransaction()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(false);

        $pdo->expects($this->never())
            ->method('rollBack');

        self::assertFalse($this->database->rollbackTransaction());
    }

    /**
     * @throws Exception
     */
    public function testRollbackTransactionWithNoRollback()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $pdo->expects($this->once())
            ->method('rollBack')
            ->willReturn(false);

        self::assertFalse($this->database->rollbackTransaction());
    }

    /**
     * @throws Exception
     * @throws QueryException
     */
    public function testRunQueryRaw()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('exec')
            ->with('a_query')
            ->willReturn(1);

        $this->database->runQueryRaw('a_query');
    }

    /**
     * @throws Exception
     * @throws QueryException
     */
    public function testRunQueryRawWithException()
    {
        $pdo = $this->createMock(PDO::class);

        $this->dbStorageHandler
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->expects($this->once())
            ->method('exec')
            ->with('a_query')
            ->willReturn(false);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Error executing the query');

        $this->database->runQueryRaw('a_query');
    }

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    public function testRunQueryWithEmptyQueryException()
    {
        $query = $this->createStub(QueryInterface::class);
        $queryData = $this->createStub(QueryDataInterface::class);
        $queryData->method('getOnErrorMessage')
                  ->willReturn('an_error');

        $query->method('getStatement')
              ->willReturn('');

        $queryData->method('getQuery')
                  ->willReturn($query);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('an_error');

        $this->database->runQuery($queryData);
    }

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    public function testRunQueryWithConnectionException()
    {
        $query = $this->createStub(QueryInterface::class);
        $queryData = $this->createStub(QueryDataInterface::class);
        $queryData->method('getOnErrorMessage')
                  ->willReturn('an_error');

        $query->method('getStatement')
              ->willReturn('test_query');

        $queryData->method('getQuery')
                  ->willReturn($query);

        $this->dbStorageHandler
            ->method('getConnection')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('test');

        $this->database->runQuery($queryData);
    }

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    public function testRunQueryWithPrepareException()
    {
        $pdo = $this->createStub(PDO::class);
        $query = $this->createStub(QueryInterface::class);
        $queryData = $this->createStub(QueryDataInterface::class);

        $queryData->method('getOnErrorMessage')
                  ->willReturn('an_error');

        $query->method('getStatement')
              ->willReturn('test_query');

        $queryData->method('getQuery')
                  ->willReturn($query);

        $this->dbStorageHandler
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->method('prepare')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('test');

        $this->database->runQuery($queryData);
    }

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    public function testRunQueryWithExecuteException()
    {
        $pdo = $this->createStub(PDO::class);
        $pdoStatement = $this->createStub(PDOStatement::class);
        $query = $this->createStub(QueryInterface::class);
        $queryData = $this->createStub(QueryDataInterface::class);

        $queryData->method('getOnErrorMessage')
                  ->willReturn('an_error');

        $query->method('getStatement')
              ->willReturn('test_query');

        $queryData->method('getQuery')
                  ->willReturn($query);

        $this->dbStorageHandler
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->method('prepare')
            ->willReturn($pdoStatement);

        $pdoStatement->method('execute')
                     ->willThrowException(new RuntimeException('test'));

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('test');

        $this->database->runQuery($queryData);
    }

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    public function testRunQueryWithConstraintException()
    {
        $pdo = $this->createStub(PDO::class);
        $pdoStatement = $this->createStub(PDOStatement::class);
        $query = $this->createStub(QueryInterface::class);
        $queryData = $this->createStub(QueryDataInterface::class);

        $queryData->method('getOnErrorMessage')
                  ->willReturn('an_error');

        $query->method('getStatement')
              ->willReturn('test_query');

        $queryData->method('getQuery')
                  ->willReturn($query);

        $this->dbStorageHandler
            ->method('getConnection')
            ->willReturn($pdo);

        $pdo->method('prepare')
            ->willReturn($pdoStatement);

        $pdoStatement->method('execute')
                     ->willThrowException(new RuntimeException('test', 23000));

        $this->expectException(ConstraintException::class);
        $this->expectExceptionMessage('Integrity constraint');
        $this->expectExceptionCode(23000);

        $this->database->runQuery($queryData);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbStorageHandler = $this->createMock(DbStorageHandler::class);

        $this->database = new Database($this->dbStorageHandler, $this->application->getEventDispatcher());
    }
}
