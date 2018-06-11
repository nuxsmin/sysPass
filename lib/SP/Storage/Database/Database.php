<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use PDO;
use PDOStatement;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;

/**
 * Class Database
 *
 * @package SP\Storage
 */
class Database implements DatabaseInterface
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
     * DB constructor.
     *
     * @param DBStorageInterface $dbHandler
     */
    public function __construct(DBStorageInterface $dbHandler)
    {
        $this->dbHandler = $dbHandler;
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
            throw new QueryException($queryData->getOnErrorMessage(), QueryException::ERROR, __u('Consulta en blanco'));
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
        } catch (\Exception $e) {
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
     * @param $getRawData  bool    realizar la consulta para obtener registro a registro
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function doQuery(QueryData $queryData, $getRawData = false)
    {
        // Limpiar valores de caché
//        $this->lastResult = [];

        /** @var PDOStatement $stmt */
        $stmt = $this->prepareQueryData($queryData);

        if (preg_match("/^(select|show)\s/i", $queryData->getQuery())) {
            $this->numFields = $stmt->columnCount();

            return new QueryResult($stmt->fetchAll());

//            $this->lastResult = $stmt->fetchAll();
//            $this->numRows = count($this->lastResult);
//
//            $queryData->setQueryNumRows($this->numRows);
        }

        return (new QueryResult())
            ->setAffectedNumRows($stmt->rowCount())
            ->setLastId($this->lastId);
    }

    /**
     * Asociar los parámetros de la consulta utilizando el tipo adecuado
     *
     * @param $queryData QueryData Los datos de la consulta
     * @param $isCount   bool   Indica si es una consulta de contador de registros
     *
     * @return \PDOStatement|false
     * @throws QueryException
     * @throws ConstraintException
     */
    private function prepareQueryData(QueryData $queryData, $isCount = false)
    {
        if ($isCount === true) {
            $query = $queryData->getQueryCount();
            $paramMaxIndex = count($queryData->getParams()) - 3;
        } else {
            $query = $queryData->getQuery();
        }

        try {
            $connection = $this->dbHandler->getConnection();

            if (is_array($queryData->getParams())) {
                $stmt = $connection->prepare($query);
                $paramIndex = 0;

                foreach ($queryData->getParams() as $param => $value) {
                    if ($isCount === true
                        && $queryData->getLimit() !== ''
                        && $paramIndex > $paramMaxIndex
                    ) {
                        continue;
                    }

                    // Si la clave es un número utilizamos marcadores de posición "?" en
                    // la consulta. En caso contrario marcadores de nombre
                    $param = is_int($param) ? $param + 1 : ':' . $param;

                    if ($param === 'blobcontent') {
                        $stmt->bindValue($param, $value, PDO::PARAM_LOB);
                    } elseif (is_int($value)) {
//                        error_log("INT: " . $param . " -> " . $value);
                        $stmt->bindValue($param, $value, PDO::PARAM_INT);
                    } else {
//                        error_log("STR: " . $param . " -> " . print_r($value, true));
                        $stmt->bindValue($param, $value);
                    }

                    $paramIndex++;
                }

                $stmt->execute();
            } else {
                $stmt = $connection->query($query);
            }

            if ($queryData->isUseKeyPair()) {
                $stmt->setFetchMode(PDO::FETCH_KEY_PAIR);
            } elseif (null !== $queryData->getMapClass()) {
                $stmt->setFetchMode(PDO::FETCH_INTO, $queryData->getMapClass());
            } elseif ($queryData->getMapClassName()) {
                $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $queryData->getMapClassName());
            } else {
                $stmt->setFetchMode(PDO::FETCH_OBJ);
            }

            $this->lastId = $connection->lastInsertId();

            return $stmt;
        } catch (\Exception $e) {
            processException($e);

            switch ($e->getCode()) {
                case '23000':
                    throw new ConstraintException(
                        __u('Restricción de integridad'),
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
     *
     * @return \PDOStatement
     * @throws QueryException
     */
    public function doQueryRaw(QueryData $queryData)
    {
        return $this->prepareQueryData($queryData);
    }
}