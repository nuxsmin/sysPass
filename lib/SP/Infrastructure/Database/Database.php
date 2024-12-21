<?php
/**
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

declare(strict_types=1);
/**
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

namespace SP\Infrastructure\Database;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryInterface;
use Exception;
use PDO;
use PDOStatement;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Database\Ports\QueryDataInterface;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class Database
 */
final class Database implements DatabaseInterface
{
    private ?int $lastId = null;

    /**
     * DB constructor.
     *
     * @param DbStorageHandler $dbStorageHandler
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        private readonly DbStorageHandler         $dbStorageHandler,
        private readonly EventDispatcherInterface $eventDispatcher
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

            $stmt = $this->prepareAndRunQuery($query);

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
                throw ConstraintException::error(__u('Integrity constraint'), $e->getMessage(), $e->getCode(), $e);
            }

            throw QueryException::critical($e->getMessage(), (string)$e->getCode(), $e->getCode(), $e);
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
            $options += [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => $buffered];
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
        if ($this->dbStorageHandler->getConnection()->exec($query) === false) {
            throw QueryException::error(__u('Error executing the query'));
        }
    }

    /**
     * Start a transaction
     *
     * @throws DatabaseException
     */
    public function beginTransaction(): bool
    {
        $conn = $this->dbStorageHandler->getConnection();

        if (!$conn->inTransaction()) {
            $result = $conn->beginTransaction();

            $this->eventDispatcher->notify(
                'database.transaction.begin',
                new Event(
                    $this,
                    EventMessage::build()->addExtra('result', $result)
                )
            );

            return $result;
        }

        logger('beginTransaction: already in transaction');

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

        $result = $conn->inTransaction() && $conn->commit();

        $this->eventDispatcher->notify(
            'database.transaction.end',
            new Event(
                $this,
                EventMessage::build()->addExtra('result', $result)
            )
        );

        return $result;
    }

    /**
     * Rollback a transaction
     *
     * @throws DatabaseException
     */
    public function rollbackTransaction(): bool
    {
        $conn = $this->dbStorageHandler->getConnection();

        $result = $conn->inTransaction() && $conn->rollBack();

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
