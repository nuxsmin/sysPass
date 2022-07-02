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

namespace SP\Infrastructure\Database;

use Exception;
use PDO;
use PDOStatement;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;

/**
 * Class Database
 *
 * @package SP\Storage
 */
final class Database implements DatabaseInterface
{
    protected int                $numRows    = 0;
    protected int                $numFields  = 0;
    protected ?array             $lastResult = null;
    protected DbStorageInterface $dbHandler;
    private ?int                 $lastId     = null;
    private EventDispatcher      $eventDispatcher;

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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function doSelect(QueryData $queryData, bool $fullCount = false): QueryResult
    {
        if ($queryData->getQuery() === '') {
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
     * Realizar una consulta a la BBDD.
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function doQuery(QueryData $queryData): QueryResult
    {
        $stmt = $this->prepareQueryData($queryData);

        $this->eventDispatcher->notifyEvent(
            'database.query',
            new Event($this, EventMessage::factory()->addDescription($queryData->getQuery()))
        );

        if (preg_match("/^(select|show)\s/i", $queryData->getQuery())) {
            $this->numFields = $stmt->columnCount();

            return new QueryResult($this->fetch($queryData, $stmt));
        }

        return (new QueryResult())->setAffectedNumRows($stmt->rowCount())->setLastId($this->lastId);
    }

    /**
     * Asociar los parámetros de la consulta utilizando el tipo adecuado
     *
     * @param  QueryData  $queryData  Los datos de la consulta
     * @param  bool  $isCount  Indica si es una consulta de contador de registros
     * @param  array  $options
     *
     * @return \PDOStatement
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    private function prepareQueryData(
        QueryData $queryData,
        bool $isCount = false,
        array $options = []
    ): PDOStatement {
        $query = $queryData->getQuery();
        $params = $queryData->getParams();

        if ($isCount === true) {
            $query = $queryData->getQueryCount();
            $params = $this->getParamsForCount($queryData);
        }

        try {
            $connection = $this->dbHandler->getConnection();

            if (count($params) !== 0) {
                $stmt = $connection->prepare($query, $options);

                foreach ($params as $param => $value) {
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

    /**
     * Strips out the unused params from the query count
     */
    private function getParamsForCount(QueryData $queryData): array
    {
        $countSelect = substr_count($queryData->getSelect(), '?');
        $countFrom = substr_count($queryData->getFrom(), '?');
        $countWhere = substr_count($queryData->getWhere(), '?');

        return array_slice($queryData->getParams(), $countSelect, $countFrom + $countWhere);
    }

    private function fetch(QueryData $queryData, PDOStatement $stmt): array
    {
        if ($queryData->isUseKeyPair()) {
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }

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
        if ($queryData->getQueryCount() === '') {
            return 0;
        }

        $queryRes = $this->prepareQueryData($queryData, true);
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

        return $this->prepareQueryData($queryData, false, $options);
    }

    /**
     * Iniciar una transacción
     */
    public function beginTransaction(): bool
    {
        $conn = $this->dbHandler->getConnection();

        if (!$conn->inTransaction()) {
            $result = $conn->beginTransaction();

            $this->eventDispatcher->notifyEvent(
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
     * Finalizar una transacción
     */
    public function endTransaction(): bool
    {
        $conn = $this->dbHandler->getConnection();

        $result = $conn->inTransaction() && $conn->commit();

        $this->eventDispatcher->notifyEvent(
            'database.transaction.end',
            new Event(
                $this,
                EventMessage::factory()->addExtra('result', $result)
            )
        );

        return $result;
    }

    /**
     * Rollback de una transacción
     */
    public function rollbackTransaction(): bool
    {
        $conn = $this->dbHandler->getConnection();

        $result = $conn->inTransaction() && $conn->rollBack();

        $this->eventDispatcher->notifyEvent(
            'database.transaction.rollback',
            new Event(
                $this,
                EventMessage::factory()->addExtra('result', $result)
            )
        );

        return $result;
    }

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