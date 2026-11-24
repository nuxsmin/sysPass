<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Infrastructure\Database;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryInterface;
use Exception;
use PDO;
use PDOStatement;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\DatabaseException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Database\Enums\DbStorageDriver;
use SP\Domain\Database\Exceptions\TransactionCommittedException;
use SP\Domain\Database\Exceptions\SavepointReleasedException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Database\Ports\QueryDataInterface;
use Throwable;
use function SP\__u;
use function SP\processException;

/**
 * Class Database
 */
final class Database implements DatabaseInterface
{
    private ?int $lastId = null;
    /** @var list<string|null> */
    private array $transactionScopes = [];
    private bool $rollbackOnly = false;

    /**
     * DB constructor.
     *
     * @param DbStorageHandler $dbStorageHandler
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly DbStorageHandler         $dbStorageHandler,
        private readonly EventDispatcherInterface   $eventDispatcher,
        private readonly ?MasterPasswordRequestLock $masterPasswordRequestLock = null
    ) {
    }

    /**
     * Perform any type of query
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function runQuery(QueryDataInterface $queryData, bool $fullCount = false): QueryResult
    {
        try {
            $query = $queryData->getQuery();

            if (empty($query->getStatement())) {
                throw QueryException::error($queryData->getOnErrorMessage(), __u('Blank query'));
            }

            $stmt = $this->prepareWithWriteFence($query);

            $this->eventDispatcher->notify(
                'database.query',
                new Event($this, EventMessage::build()->addDescription($query->getStatement()))
            );

            if ($query instanceof SelectInterface) {
                if ($fullCount === true) {
                    return QueryResult::withTotalNumRows(
                        $this->fetch($stmt, $queryData->getMapClassName()),
                        $this->getFullRowCount($queryData)
                    );
                }

                return new QueryResult($this->fetch($stmt, $queryData->getMapClassName()));
            }

            return new QueryResult(null, $stmt->rowCount(), $this->lastId);
        } catch (ConstraintException|QueryException $e) {
            processException($e);

            throw $e;
        }
    }

    /** Hold the rotation fence on the connection committing the mutation. */
    private function prepareWithWriteFence(QueryInterface $query): PDOStatement
    {
        if ($query instanceof SelectInterface || $this->masterPasswordRequestLock === null
            || !$this->masterPasswordRequestLock->isActive()) {
            return $this->prepareAndRunQuery($query);
        }

        $connection = $this->dbStorageHandler->getConnection();
        $ownTransaction = !$connection->inTransaction();
        if ($ownTransaction) {
            $connection->beginTransaction();
        }
        try {
            $this->masterPasswordRequestLock->protectWrite($connection);
            $statement = $this->prepareAndRunQuery($query);
            if ($ownTransaction) {
                $connection->commit();
            }
            return $statement;
        } catch (Throwable $e) {
            if ($ownTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Asociar los parámetros de la consulta utilizando el tipo adecuado
     *
     * @param QueryInterface $query Los datos de la consulta
     * @param array $options
     *
     * @return PDOStatement
     * @throws ConstraintException
     * @throws QueryException
     */
    private function prepareAndRunQuery(QueryInterface $query, array $options = []): PDOStatement
    {
        $this->assertTransactionUsable();
        try {
            $connection = $this->dbStorageHandler->getConnection();

            $stmt = $connection->prepare($query->getStatement(), $options);

            foreach ($query->getBindValues() as $param => $value) {
                $type = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    default => PDO::PARAM_STR
                };

                $stmt->bindValue($param, $value, $type);
            }

            $stmt->execute();

            $this->lastId = (int)$connection->lastInsertId();

            return $stmt;
        } catch (Exception $e) {
            processException($e);

            if ((int)$e->getCode() === 23000) {
                throw ConstraintException::error(__u('Integrity constraint'), $e->getMessage(), (int)$e->getCode(), $e);
            }

            throw QueryException::critical($e->getMessage(), (string)$e->getCode(), (int)$e->getCode(), $e);
        }
    }

    private function fetch(PDOStatement $stmt, ?string $class = null): array
    {
        $fetchArgs = [PDO::FETCH_DEFAULT];

        if ($class) {
            $fetchArgs = [PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $class];
        }

        return $stmt->fetchAll(...$fetchArgs);
    }

    /**
     * Obtener el número de filas de una consulta realizada
     *
     * @param QueryDataInterface $queryData
     * @return int Número de filas de la consulta
     * @throws ConstraintException
     * @throws QueryException
     */
    private function getFullRowCount(QueryDataInterface $queryData): int
    {
        return (int)$this->prepareAndRunQuery($queryData->getQueryCount())->fetchColumn();
    }

    /**
     * Don't fetch records and return prepared statement
     *
     * @param QueryData $queryData
     * @param array $options
     * @param int $mode Fech mode
     * @param bool|null $buffered Set buffered behavior (useful for big datasets)
     *
     * @return PDOStatement
     * @throws ConstraintException
     * @throws QueryException
     */
    public function doFetchWithOptions(
        QueryDataInterface $queryData,
        array              $options = [],
        int                $mode = PDO::FETCH_DEFAULT,
        ?bool              $buffered = true
    ): iterable {
        if ($this->dbStorageHandler->getDriver() === DbStorageDriver::mysql) {
            $options += [PdoMysqlAttributes::useBufferedQuery() => $buffered];
        }

        $stmt = $this->prepareAndRunQuery($queryData->getQuery(), $options);

        while (($row = $stmt->fetch($mode)) !== false) {
            yield $row;
        }
    }

    /**
     * Execute a raw query
     *
     * @param string $query
     * @throws QueryException
     * @throws DatabaseException
     */
    public function runQueryRaw(string $query): void
    {
        $this->assertTransactionUsable();
        $connection = $this->dbStorageHandler->getConnection();
        $fence = $this->masterPasswordRequestLock !== null && $this->masterPasswordRequestLock->isActive();
        // DDL and transaction-control statements can implicitly commit and release
        // the fence before the statement completes. Fail closed during requests.
        if ($fence && (!preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE)\b/i', $query)
            || str_contains($query, ';'))) {
            throw QueryException::error('Raw statement is not permitted while the master password request lock is held');
        }
        $ownTransaction = $fence && !$connection->inTransaction();
        if ($ownTransaction) {
            $connection->beginTransaction();
        }
        try {
            if ($fence) {
                $this->masterPasswordRequestLock->protectWrite($connection);
            }
            if ($connection->exec($query) === false) {
                throw QueryException::error(__u('Error executing the query'));
            }
            if ($ownTransaction) {
                $connection->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Start a transaction
     *
     * @throws DatabaseException
     */
    public function beginTransaction(): bool
    {
        $this->assertTransactionUsable();
        $conn = $this->dbStorageHandler->getConnection();

        if (!$conn->inTransaction()) {
            $result = $conn->beginTransaction();
            if ($result) {
                $this->transactionScopes[] = null;
            }

            try {
                $this->eventDispatcher->notify(
                    'database.transaction.begin',
                    new Event($this, EventMessage::build()->addExtra('result', $result))
                );
            } catch (Throwable $e) {
                if ($result) {
                    $this->rollbackTransaction();
                }
                throw $e;
            }

            return $result;
        }

        $savepoint = 'sp_scope_' . count($this->transactionScopes);
        if ($conn->exec('SAVEPOINT ' . $savepoint) === false) {
            return false;
        }
        $this->transactionScopes[] = $savepoint;
        return true;
    }

    /**
     * Finish a transaction
     *
     * @throws DatabaseException
     */
    public function endTransaction(): bool
    {
        $conn = $this->dbStorageHandler->getConnection();

        if ($this->rollbackOnly) {
            $rolledBack = !$conn->inTransaction() || $conn->rollBack();
            if ($rolledBack) {
                $this->transactionScopes = [];
                $this->rollbackOnly = false;
            }
            return false;
        }

        $scope = end($this->transactionScopes);
        $committing = $this->transactionScopes === [] || $scope === null || $scope === false;
        if ($this->transactionScopes !== [] && $scope !== null && $scope !== false) {
            if (!$conn->inTransaction()) {
                return false;
            }
            $result = $conn->exec('RELEASE SAVEPOINT ' . $scope) !== false;
            if ($result) {
                array_pop($this->transactionScopes);
                try {
                    $this->notifyTransactionEnd(true);
                } catch (Throwable $e) {
                    throw new SavepointReleasedException('Savepoint released, but its completion event failed', 0, $e);
                }
            }
            return $result;
        } else {
            $result = $conn->inTransaction() && $conn->commit();
        }
        if ($result && $this->transactionScopes !== []) {
            array_pop($this->transactionScopes);
        }

        try {
            $this->notifyTransactionEnd($result);
        } catch (Throwable $e) {
            if ($result && $committing) {
                throw new TransactionCommittedException('Transaction committed, but its completion event failed', 0, $e);
            }
            throw $e;
        }

        return $result;
    }

    private function notifyTransactionEnd(bool $result): void
    {
        $this->eventDispatcher->notify(
            'database.transaction.end',
            new Event($this, EventMessage::build()->addExtra('result', $result))
        );
    }

    private function assertTransactionUsable(): void
    {
        if ($this->rollbackOnly) {
            throw QueryException::error('Transaction must be rolled back after a failed savepoint rollback');
        }
    }

    /**
     * Rollback a transaction
     *
     * @throws DatabaseException
     */
    public function rollbackTransaction(): bool
    {
        $conn = $this->dbStorageHandler->getConnection();

        $scope = end($this->transactionScopes);
        $result = false;
        try {
            if (!$conn->inTransaction()) {
                $this->transactionScopes = [];
            } elseif ($this->transactionScopes !== [] && $scope !== null && $scope !== false) {
                $result = $conn->exec('ROLLBACK TO SAVEPOINT ' . $scope) !== false;
                if ($result) {
                    $result = $conn->exec('RELEASE SAVEPOINT ' . $scope) !== false;
                }
            } else {
                $result = $conn->rollBack();
            }
        } catch (\PDOException $e) {
            $result = false;
        } finally {
            if (!$result && $scope !== null && $scope !== false) {
                $this->rollbackOnly = true;
            }
            if ($this->transactionScopes !== []) {
                array_pop($this->transactionScopes);
            }
            if ($result && $this->transactionScopes === []) {
                $this->rollbackOnly = false;
            }
        }

        $this->eventDispatcher->notify(
            'database.transaction.rollback',
            new Event(
                $this,
                EventMessage::build()->addExtra('result', $result)
            )
        );

        return $result;
    }
}
