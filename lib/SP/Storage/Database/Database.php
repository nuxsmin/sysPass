<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Storage\Database;

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
    /**
     * @var int Número de registros obtenidos
     */
    protected $numRows = 0;
    /**
     * @var int Número de campos de la consulta
     */
    protected $numFields = 0;
    /**
     * @var array Resultados de la consulta
     */
    protected $lastResult;
    /**
     * @var DBStorageInterface
     */
    protected $dbHandler;
    /**
     * @var int Último Id de elemento insertado/actualizado
     */
    private $lastId;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * DB constructor.
     *
     * @param DBStorageInterface $dbHandler
     * @param EventDispatcher    $eventDispatcher
     */
    public function __construct(DBStorageInterface $dbHandler, EventDispatcher $eventDispatcher)
    {
        $this->dbHandler = $dbHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return int
     */
    public function getNumRows()
    {
        return $this->numRows;
    }

    /**
     * @return int
     */
    public function getNumFields()
    {
        return $this->numFields;
    }

    /**
     * @return array
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * @return int
     */
    public function getLastId()
    {
        return $this->lastId;
    }

    /**
     * @return DBStorageInterface
     */
    public function getDbHandler()
    {
        return $this->dbHandler;
    }

    /**
     * @param QueryData $queryData
     * @param bool      $fullCount
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function doSelect(QueryData $queryData, $fullCount = false)
    {
        if ($queryData->getQuery() === '') {
            throw new QueryException($queryData->getOnErrorMessage(), QueryException::ERROR, __u('Blank query'));
        }

        try {
            $queryResult = $this->doQuery($queryData);

            if ($fullCount === true) {
                $queryResult->setTotalNumRows($this->getFullRowCount($queryData));
            }

            return $queryResult;
        } catch (ConstraintException $e) {
            processException($e);

            throw $e;
        } catch (QueryException $e) {
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
     * @param $queryData   QueryData Los datos de la consulta
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function doQuery(QueryData $queryData): QueryResult
    {
        $stmt = $this->prepareQueryData($queryData);

        $this->eventDispatcher->notifyEvent('database.query',
            new Event($this, EventMessage::factory()
                ->addDescription($queryData->getQuery())
            )
        );

        if (preg_match("/^(select|show)\s/i", $queryData->getQuery())) {
            $this->numFields = $stmt->columnCount();

            return new QueryResult($this->fetch($queryData, $stmt));
        }

        return (new QueryResult())
            ->setAffectedNumRows($stmt->rowCount())
            ->setLastId($this->lastId);
    }

    /**
     * Asociar los parámetros de la consulta utilizando el tipo adecuado
     *
     * @param QueryData $queryData Los datos de la consulta
     * @param bool      $isCount   Indica si es una consulta de contador de registros
     * @param array     $options
     *
     * @return PDOStatement
     * @throws ConstraintException
     * @throws QueryException
     */
    private function prepareQueryData(QueryData $queryData, $isCount = false, array $options = [])
    {
        $query = $queryData->getQuery();
        $params = $queryData->getParams();

        if ($isCount === true) {
            $query = $queryData->getQueryCount();
            $params = $this->getParamsForCount($queryData);
        }

        try {
            $connection = $this->dbHandler->getConnection();

            if (!empty($params)) {
                $stmt = $connection->prepare($query, $options);

                foreach ($params as $param => $value) {
                    // Si la clave es un número utilizamos marcadores de posición "?" en
                    // la consulta. En caso contrario marcadores de nombre
                    $param = is_int($param) ? $param + 1 : ':' . $param;

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

            switch ((int)$e->getCode()) {
                case 23000:
                    throw new ConstraintException(
                        __u('Integrity constraint'),
                        ConstraintException::ERROR,
                        $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
            }

            throw new QueryException($e->getMessage(), QueryException::CRITICAL, $e->getCode(), 0, $e);
        }
    }

    /**
     * Strips out the unused params from the query count
     *
     * @param QueryData $queryData
     *
     * @return array
     */
    private function getParamsForCount(QueryData $queryData)
    {
        $countSelect = substr_count($queryData->getSelect(), '?');
        $countFrom = substr_count($queryData->getFrom(), '?');
        $countWhere = substr_count($queryData->getWhere(), '?');

        return array_slice($queryData->getParams(), $countSelect, $countFrom + $countWhere);
    }

    /**
     * @param QueryData    $queryData
     * @param PDOStatement $stmt
     *
     * @return array
     */
    private function fetch(QueryData $queryData, PDOStatement $stmt): array
    {
        if ($queryData->isUseKeyPair()) {
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } elseif ($queryData->getMapClassName()) {
            return $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $queryData->getMapClassName());
        }

        return $stmt->fetchAll();
    }

    /**
     * Obtener el número de filas de una consulta realizada
     *
     * @param $queryData QueryData Los datos de la consulta
     *
     * @return int Número de files de la consulta
     * @throws SPException
     */
    public function getFullRowCount(QueryData $queryData)
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
     * @param QueryData $queryData
     * @param array     $options
     * @param bool      $buffered Set buffered behavior (useful for big datasets)
     *
     * @return PDOStatement
     * @throws ConstraintException
     * @throws QueryException
     */
    public function doQueryRaw(QueryData $queryData, array $options = [], bool $buffered = null)
    {
        if ($buffered === false
            && $this->dbHandler instanceof MySQLHandler
        ) {
            $this->dbHandler
                ->getConnection()
                ->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        return $this->prepareQueryData($queryData, $options);
    }

    /**
     * Iniciar una transacción
     *
     * @return bool
     */
    public function beginTransaction()
    {
        $conn = $this->dbHandler->getConnection();

        if (!$conn->inTransaction()) {
            $result = $conn->beginTransaction();

            $this->eventDispatcher->notifyEvent('database.transaction.begin',
                new Event($this, EventMessage::factory()->addExtra('result', $result)));

            return $result;
        } else {
            logger('beginTransaction: already in transaction');

            return true;
        }
    }

    /**
     * Finalizar una transacción
     *
     * @return bool
     */
    public function endTransaction()
    {
        $conn = $this->dbHandler->getConnection();

        $result = $conn->inTransaction() && $conn->commit();

        $this->eventDispatcher->notifyEvent('database.transaction.end',
            new Event($this, EventMessage::factory()->addExtra('result', $result)));

        return $result;
    }

    /**
     * Rollback de una transacción
     *
     * @return bool
     */
    public function rollbackTransaction()
    {
        $conn = $this->dbHandler->getConnection();

        $result = $conn->inTransaction() && $conn->rollBack();

        $this->eventDispatcher->notifyEvent('database.transaction.rollback',
            new Event($this, EventMessage::factory()->addExtra('result', $result)));

        return $result;
    }

    /**
     * @param $table
     *
     * @return array
     */
    public function getColumnsForTable($table): array
    {
        $conn = $this->dbHandler->getConnection()->query("SELECT * FROM $table LIMIT 0");
        $columns = [];

        for ($i = 0; $i < $conn->columnCount(); $i++) {
            $columns[] = $conn->getColumnMeta($i)['name'];
        }

        return $columns;
    }
}