<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Storage;

use PDO;
use PDOStatement;
use SP\Core\DiFactory;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Messages\LogMessage;
use SP\Log\Log;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar las operaciones con la BBDD de sysPass.
 */
class DB
{
    /**
     * @var int
     */
    public static $lastId;
    /**
     * @var bool Contar el número de filas totales
     */
    private static $fullRowCount = false;
    /**
     * @var int Número de registros obtenidos
     */
    private $numRows = 0;
    /**
     * @var int Número de campos de la consulta
     */
    private $numFields = 0;
    /**
     * @var array Resultados de la consulta
     */
    private $lastResult;

    /**
     * @return int
     */
    public static function getLastId()
    {
        return self::$lastId;
    }

    /**
     * Devolver los resultados en array
     *
     * @param QueryData $queryData
     * @return array
     */
    public static function getResultsArray(QueryData $queryData)
    {
        $results = self::getResults($queryData);

        if ($results === false) {
            return [];
        }

        return is_object($results) ? [$results] : $results;
    }

    /**
     * Obtener los resultados de una consulta.
     *
     * @param  $queryData  QueryData Los datos de la consulta
     * @return mixed devuelve bool si hay un error. Devuelve array con el array de registros devueltos
     */
    public static function getResults(QueryData $queryData)
    {
        if ($queryData->getQuery() === '') {
            self::resetVars();
            return false;
        }

        try {
            $db = new DB();
            $db->doQuery($queryData);

            if (self::$fullRowCount === true) {
                $db->getFullRowCount($queryData);
            }
        } catch (SPException $e) {
            $queryData->setQueryStatus($e->getCode());

            self::logDBException($queryData->getQuery(), $e, __FUNCTION__);
            return false;
        }

        self::resetVars();

        if ($db->numRows === 1 && !$queryData->isUseKeyPair()) {
            return $db->lastResult[0];
        }

        return $db->lastResult;
    }

    /**
     * Restablecer los atributos estáticos
     */
    private static function resetVars()
    {
        self::$fullRowCount = false;
    }

    /**
     * Realizar una consulta a la BBDD.
     *
     * @param $queryData   QueryData Los datos de la consulta
     * @param $getRawData  bool    realizar la consulta para obtener registro a registro
     * @return PDOStatement|array
     * @throws SPException
     */
    public function doQuery(QueryData $queryData, $getRawData = false)
    {
        $isSelect = preg_match("/^(select|show)\s/i", $queryData->getQuery());

        // Limpiar valores de caché
        $this->lastResult = [];

        /** @var PDOStatement $queryRes */
        $queryRes = $this->prepareQueryData($queryData);

        if ($isSelect) {
            if ($getRawData) {
                return $queryRes;
            }

            $this->numFields = $queryRes->columnCount();
            $this->lastResult = $queryRes->fetchAll();
            $this->numRows = count($this->lastResult);

            $queryData->setQueryNumRows($this->numRows);
        } else {
            $queryData->setQueryNumRows($queryRes->rowCount());
        }

        return $queryRes;
    }

    /**
     * Asociar los parámetros de la consulta utilizando el tipo adecuado
     *
     * @param $queryData QueryData Los datos de la consulta
     * @param $isCount   bool   Indica si es una consulta de contador de registros
     * @return \PDOStatement|false
     * @throws SPException
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
            $db = DiFactory::getDBStorage()->getConnection();

            if (is_array($queryData->getParams())) {
                $stmt = $db->prepare($query);
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
                        $stmt->bindValue($param, $value, PDO::PARAM_STR);
                    }

                    $paramIndex++;
                }

                $stmt->execute();
            } else {
                $stmt = $db->query($query);
            }

            if ($queryData->isUseKeyPair() === true) {
                $stmt->setFetchMode(PDO::FETCH_KEY_PAIR);
            } elseif (null !== $queryData->getMapClass()) {
                $stmt->setFetchMode(PDO::FETCH_INTO, $queryData->getMapClass());
            } elseif ($queryData->getMapClassName()) {
                $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $queryData->getMapClassName());
            } else {
                $stmt->setFetchMode(PDO::FETCH_OBJ);
            }

            DB::$lastId = $db->lastInsertId();

            return $stmt;
        } catch (SPException $e) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            debugLog(sprintf('Exception: %s - %s', $e->getMessage(), $e->getHint()));
            debugLog(ob_get_clean());

            throw $e;
        } catch (\Exception $e) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            debugLog('Exception: ' . $e->getMessage());
            debugLog(ob_get_clean());

            throw new SPException(SPException::SP_CRITICAL, $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Obtener el número de filas de una consulta realizada
     *
     * @param $queryData QueryData Los datos de la consulta
     * @return int Número de files de la consulta
     * @throws SPException
     */
    private function getFullRowCount(QueryData $queryData)
    {
        if ($queryData->getQueryCount() === '') {
            return 0;
        }

        $queryRes = $this->prepareQueryData($queryData, true);
        $num = (int)$queryRes->fetchColumn();
        $queryRes->closeCursor();
        $queryData->setQueryNumRows($num);
    }

    /**
     * Método para registar los eventos de BD en el log
     *
     * @param string     $query La consulta que genera el error
     * @param \Exception $e
     * @param string     $queryFunction
     */
    private static function logDBException($query, \Exception $e, $queryFunction)
    {
        $caller = Util::traceLastCall($queryFunction);

        $LogMessage = new LogMessage();
        $LogMessage->setAction($caller);
        $LogMessage->addDescription(__('Error en la consulta', false));
        $LogMessage->addDescription(sprintf('%s (%s)', $e->getMessage(), $e->getCode()));
        $LogMessage->addDetails('SQL', DBUtil::escape($query));

        debugLog($LogMessage->getDescription(true), true);
        debugLog($LogMessage->getDetails());

        // Solo registrar eventos de ls BD si no son consultas del registro de eventos
        if ($caller !== 'writeLog') {
            $Log = new Log($LogMessage);
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();
        }
    }

    /**
     * Devolver los resultados como objeto PDOStatement
     *
     * @param QueryData $queryData
     * @return PDOStatement|false
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function getResultsRaw(QueryData $queryData)
    {
        try {
            $db = new DB();
            return $db->doQuery($queryData, true);
        } catch (SPException $e) {
            self::logDBException($queryData->getQuery(), $e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Realizar una consulta y devolver el resultado sin datos
     *
     * @param QueryData $queryData Los datos para realizar la consulta
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public static function getQuery(QueryData $queryData)
    {
        if (null === $queryData->getOnErrorMessage()) {
            $errorMessage = __('Error en la consulta', false);
        } else {
            $errorMessage = $queryData->getOnErrorMessage();
        }

        if ($queryData->getQuery() === '') {
            throw new QueryException(SPException::SP_ERROR, $errorMessage, __('Consulta en blanco', false));
        }

        try {
            $db = new DB();
            $db->doQuery($queryData);

            return true;
        } catch (SPException $e) {
            $queryData->setQueryStatus($e->getCode());

            self::logDBException($queryData->getQuery(), $e, __FUNCTION__);

            if ($e->getCode() === 23000) {
                throw new ConstraintException(SPException::SP_ERROR, __('Restricción de integridad', false), $e->getMessage(), $e->getCode());
            }

            throw new QueryException(SPException::SP_ERROR, $errorMessage, $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Establecer si es necesario contar el número total de resultados devueltos
     */
    public static function setFullRowCount()
    {
        self::$fullRowCount = true;
    }

    /**
     * Iniciar una transacción
     *
     * @return bool
     */
    public static function beginTransaction()
    {
        $conn = DiFactory::getDBStorage()->getConnection();

        return !$conn->inTransaction() && $conn->beginTransaction();
    }

    /**
     * Finalizar una transacción
     */
    public static function endTransaction()
    {
        $conn = DiFactory::getDBStorage()->getConnection();

        return $conn->inTransaction() && $conn->commit();
    }

    /**
     * Rollback de una transacción
     */
    public static function rollbackTransaction()
    {
        $conn = DiFactory::getDBStorage()->getConnection();

        return $conn->inTransaction() && $conn->rollBack();
    }
}
