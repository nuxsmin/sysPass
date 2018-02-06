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

use PDOStatement;
use SP\Bootstrap;
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
class DbWrapper
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
     * @return int
     */
    public static function getLastId()
    {
        return self::$lastId;
    }

    /**
     * Devolver los resultados en array
     *
     * @param QueryData         $queryData
     * @param DatabaseInterface $db
     * @return array
     */
    public static function getResultsArray(QueryData $queryData, DatabaseInterface $db = null)
    {
        // FIXME: remove
        if ($db === null) {
            /** @var Database $db */
            $db = Bootstrap::getContainer()->get(Database::class);
        }

        $results = self::getResults($queryData, $db);

        if ($results === false) {
            return [];
        }

        return is_object($results) ? [$results] : $results;
    }

    /**
     * Obtener los resultados de una consulta.
     *
     * @param QueryData         $queryData QueryData Los datos de la consulta
     * @param DatabaseInterface $db
     * @return mixed devuelve bool si hay un error. Devuelve array con el array de registros devueltos
     */
    public static function getResults(QueryData $queryData, DatabaseInterface $db = null)
    {
        if ($queryData->getQuery() === '') {
            self::resetVars();
            return false;
        }

        try {
            // FIXME: remove
            if ($db === null) {
                /** @var Database $db */
                $db = Bootstrap::getContainer()->get(Database::class);
            }

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

        if ($db->getNumRows() === 1 && !$queryData->isUseKeyPair()) {
            return $db->getLastResult()[0];
        }

        return $db->getLastResult();
    }

    /**
     * Restablecer los atributos estáticos
     */
    private static function resetVars()
    {
        self::$fullRowCount = false;
    }

    /**
     * Método para registar los eventos de BD en el log
     *
     * @param string     $query La consulta que genera el error
     * @param \Exception $e
     * @param string     $queryFunction
     * @throws \SP\Core\Dic\ContainerException
     */
    private static function logDBException($query, \Exception $e, $queryFunction)
    {
        $caller = Util::traceLastCall($queryFunction);

        $LogMessage = new LogMessage();
        $LogMessage->setAction($caller);
        $LogMessage->addDescription(__u('Error en la consulta'));
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
     * @param QueryData         $queryData
     * @param DatabaseInterface $db
     * @return PDOStatement|false
     * @throws SPException
     * @throws \SP\Core\Dic\ContainerException
     */
    public static function getResultsRaw(QueryData $queryData, DatabaseInterface $db = null)
    {
        try {
            // FIXME: remove
            if ($db === null) {
                /** @var Database $db */
                $db = Bootstrap::getContainer()->get(Database::class);
            }

            return $db->doQuery($queryData, true);
        } catch (SPException $e) {
            self::logDBException($queryData->getQuery(), $e, __FUNCTION__);

            throw $e;
        }
    }

    /**
     * Realizar una consulta y devolver el resultado sin datos
     *
     * @param QueryData         $queryData Los datos para realizar la consulta
     * @param DatabaseInterface $db
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     * @throws \SP\Core\Dic\ContainerException
     */
    public static function getQuery(QueryData $queryData, DatabaseInterface $db = null)
    {
        if (null === $queryData->getOnErrorMessage()) {
            $errorMessage = __u('Error en la consulta');
        } else {
            $errorMessage = $queryData->getOnErrorMessage();
        }

        if ($queryData->getQuery() === '') {
            throw new QueryException(SPException::SP_ERROR, $errorMessage, __u('Consulta en blanco'));
        }

        try {
            // FIXME: remove
            if ($db === null) {
                /** @var Database $db */
                $db = Bootstrap::getContainer()->get(Database::class);
            }

            $db->doQuery($queryData);

            return true;
        } catch (SPException $e) {
            $queryData->setQueryStatus($e->getCode());

            self::logDBException($queryData->getQuery(), $e, __FUNCTION__);

            switch ($e->getCode()) {
                case 23000:
                    throw new ConstraintException(SPException::SP_ERROR, __u('Restricción de integridad'), $e->getMessage(), $e->getCode());
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
     * @param DatabaseInterface $db
     * @return bool
     */
    public static function beginTransaction(DatabaseInterface $db)
    {
        $conn = $db->getDbHandler()->getConnection();

        return !$conn->inTransaction() && $conn->beginTransaction();
    }

    /**
     * Finalizar una transacción
     *
     * @param DatabaseInterface $db
     * @return bool
     */
    public static function endTransaction(DatabaseInterface $db)
    {
        $conn = $db->getDbHandler()->getConnection();

        return $conn->inTransaction() && $conn->commit();
    }

    /**
     * Rollback de una transacción
     *
     * @param DatabaseInterface $db
     * @return bool
     */
    public static function rollbackTransaction(DatabaseInterface $db)
    {
        $conn = $db->getDbHandler()->getConnection();

        return $conn->inTransaction() && $conn->rollBack();
    }
}
