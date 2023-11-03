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

namespace SP\Infrastructure\Database;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryInterface;
use Exception;
use PDO;
use PDOStatement;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class Database
 *
 * @package SP\Storage
 */
final class Database implements DatabaseInterface
{
    protected DbStorageInterface $dbHandler;
    protected int                $numRows    = 0;
    protected int                $numFields  = 0;
    protected ?array             $lastResult = null;
    private EventDispatcher      $eventDispatcher;
    private ?int                 $lastId     = null;

    /**
     * DB constructor.
     *
     * @param  DbStorageInterface  $dbHandler
     * @param  EventDispatcher  $eventDispatcher
     */
    public function __construct(
        DbStorageInterface $dbHandler,
        EventDispatcher $eventDispatcher
    ) {
        $this->dbHandler = $dbHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getNumRows(): int
    {
        return $this->numRows;
    }

    public function getNumFields(): int
    {
        return $this->numFields;
    }

    public function getLastResult(): ?array
    {
        return $this->lastResult;
    }

    public function getLastId(): ?int
    {
        return $this->lastId;
    }

    public function getDbHandler(): DbStorageInterface
    {
        return $this->dbHandler;
    }

    /**
     * Perform a SELECT type query
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function doSelect(QueryData $queryData, bool $fullCount = false): QueryResult
    {
        if ($queryData->getQuery()->getStatement()) {
            throw new QueryException($queryData->getOnErrorMessage(), SPException::ERROR, __u('Blank query'));
        }

        try {
            $queryResult = $this->doQuery($queryData);

            if ($fullCount === true) {
                $queryResult->setTotalNumRows($this->getFullRowCount($queryData));
            }

            return $queryResult;
        } catch (ConstraintException|QueryException $e) {
            processException($e);

            throw $e;
        } catch (Exception $e) {
            processException($e);

            throw new QueryException(
                $queryData->getOnErrorMessage(),
                SPException::ERROR,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Perform any type of query
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function doQuery(QueryData $queryData): QueryResult
    {
        $stmt = $this->prepareQueryData($queryData->getQuery());

        $this->eventDispatcher->notify(
            'database.query',
            new Event($this, EventMessage::factory()->addDescription($queryData->getQuery()->getStatement()))
        );

        if ($queryData->getQuery() instanceof SelectInterface) {
            $this->numFields = $stmt->columnCount();

            return new QueryResult($this->fetch($queryData, $stmt));
        }

        return (new QueryResult())->setAffectedNumRows($stmt->rowCount())->setLastId($this->lastId);
    }

    /**
     * Asociar los parámetros de la consulta utilizando el tipo adecuado
     *
     * @param  QueryInterface  $query  Los datos de la consulta
     * @param  array  $options
     *
     * @return \PDOStatement
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    private function prepareQueryData(
        QueryInterface $query,
        array $options = []
    ): PDOStatement {
        try {
            $connection = $this->dbHandler->getConnection();

            if (count($query->getBindValues()) !== 0) {
                $stmt = $connection->prepare($query->getStatement(), $options);

                foreach ($query->getBindValues() as $param => $value) {
                    // Si la clave es un número utilizamos marcadores de posición "?" en
                    // la consulta. En caso contrario marcadores de nombre
                    $param = is_int($param) ? $param + 1 : ':'.$param;

                    if ($param === 'blobcontent') {
                        $stmt->bindValue($param, $value, PDO::PARAM_LOB);
                    } elseif (is_int($value)) {
                        $stmt->bindValue($param, $value, PDO::PARAM_INT);
                    } else {
                        $stmt->bindValue($param, $value);
                    }
                }

                $stmt->execute();
            } else {
                $stmt = $connection->query($query);
            }

            $this->lastId = $connection->lastInsertId();

            return $stmt;
        } catch (Exception $e) {
            processException($e);

            if ((int)$e->getCode() === 23000) {
                throw new ConstraintException(
                    __u('Integrity constraint'),
                    SPException::ERROR,
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }

            throw new QueryException(
                $e->getMessage(),
                SPException::CRITICAL,
                $e->getCode(),
                0,
                $e
            );
        }
    }

    private function fetch(QueryData $queryData, PDOStatement $stmt): array
    {
        if ($queryData->getMapClassName()) {
            return $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $queryData->getMapClassName());
        }

        return $stmt->fetchAll();
    }

    /**
     * Obtener el número de filas de una consulta realizada
     *
     * @return int Número de filas de la consulta
     * @throws SPException
     */
    public function getFullRowCount(QueryData $queryData): int
    {
        $queryRes = $this->prepareQueryData($queryData->getQueryCount());
        $num = (int)$queryRes->fetchColumn();
        $queryRes->closeCursor();

        return $num;
    }

    /**
     * Don't fetch records and return prepared statement
     *
     * @param  QueryData  $queryData
     * @param  array  $options
     * @param  bool|null  $buffered  Set buffered behavior (useful for big datasets)
     *
     * @return PDOStatement
     * @throws ConstraintException
     * @throws QueryException|DatabaseException
     */
    public function doQueryRaw(
        QueryData $queryData,
        array $options = [],
        ?bool $buffered = null
    ): PDOStatement {
        if ($buffered === false && $this->dbHandler instanceof MysqlHandler) {
            $this->dbHandler
                ->getConnection()
                ->setAttribute(
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                    false
                );
        }

        return $this->prepareQueryData($queryData->getQuery(), $options);
    }

    /**
     * Start a transaction
     */
    public function beginTransaction(): bool
    {
        $conn = $this->dbHandler->getConnection();

        if (!$conn->inTransaction()) {
            $result = $conn->beginTransaction();

            $this->eventDispatcher->notify(
                'database.transaction.begin',
                new Event(
                    $this,
                    EventMessage::factory()->addExtra('result', $result)
                )
            );

            return $result;
        }

        logger('beginTransaction: already in transaction');

        return true;
    }

    /**
     * Finish a transaction
     */
    public function endTransaction(): bool
    {
        $conn = $this->dbHandler->getConnection();

        $result = $conn->inTransaction() && $conn->commit();

        $this->eventDispatcher->notify(
            'database.transaction.end',
            new Event(
                $this,
                EventMessage::factory()->addExtra('result', $result)
            )
        );

        return $result;
    }

    /**
     * Rollback a transaction
     */
    public function rollbackTransaction(): bool
    {
        $conn = $this->dbHandler->getConnection();

        $result = $conn->inTransaction() && $conn->rollBack();

        $this->eventDispatcher->notify(
            'database.transaction.rollback',
            new Event(
                $this,
                EventMessage::factory()->addExtra('result', $result)
            )
        );

        return $result;
    }

    /**
     * Get the columns of a table
     *
     * @param  string  $table
     *
     * @return array
     */
    public function getColumnsForTable(string $table): array
    {
        $conn = $this->dbHandler->getConnection()->query("SELECT * FROM `$table` LIMIT 0");
        $columns = [];

        for ($i = 0; $i < $conn->columnCount(); $i++) {
            $columns[] = $conn->getColumnMeta($i)['name'];
        }

        return $columns;
    }
}
