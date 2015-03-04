<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class DBConnectionFactory
 *
 * Esta clase se encarga de crear las conexiones a la BD
 */
class DBConnectionFactory
{
    private static $factory;
    private $db;

    public static function getFactory()
    {
        if (!self::$factory) {
//             FIXME
//            error_log('NEW FACTORY');
            self::$factory = new DBConnectionFactory();
        }

        return self::$factory;
    }

    /**
     * Realizar la conexión con la BBDD.
     * Esta función utiliza PDO para conectar con la base de datos.
     *
     * @throws Exception
     * @return object|bool
     */

    public function getConnection()
    {
        if (!$this->db) {
//             FIXME
//            error_log('NEW DB_CONNECTION');
            $isInstalled = SP_Config::getValue('installed');

            $dbhost = SP_Config::getValue("dbhost");
            $dbuser = SP_Config::getValue("dbuser");
            $dbpass = SP_Config::getValue("dbpass");
            $dbname = SP_Config::getValue("dbname");

            if (empty($dbhost) || empty($dbuser) || empty($dbpass) || empty($dbname)) {
                if ($isInstalled) {
                    SP_Init::initError(_('No es posible conectar con la BD'), _('Compruebe los datos de conexión'));
                } else {
                    throw new SPDatabaseException(_('No es posible conectar con la BD'), 1);
                }
            }

            try {
                $dsn = 'mysql:host=' . $dbhost . ';dbname=' . $dbname . ';charset=utf8';
//                $this->db = new PDO($dsn, $dbuser, $dbpass, array(PDO::ATTR_PERSISTENT => true));
                $this->db = new PDO($dsn, $dbuser, $dbpass);
            } catch (PDOException $e) {
                if ($isInstalled) {
                    if ($this->db->connect_errno === 1049) {
                        SP_Config::setValue('installed', '0');
                    }

                    SP_Init::initError(_('No es posible conectar con la BD'), 'Error ' . $this->db->errorCode() . ': ' . $this->db->errorInfo());
                } else {
                    throw new SPDatabaseException($e->getMessage(), $e->getCode());
                }
            }
        }

        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->db;
    }
}

/**
 * Class SPDatabaseException
 *
 * Clase para excepciones de BD de sysPass
 */
class SPDatabaseException extends Exception
{
}

/**
 * Esta clase es la encargada de realizar las operaciones con la BBDD de sysPass.
 */
class DB
{
    static $txtError = '';
    static $numError = 0;
    static $last_num_rows = 0;
    static $lastId = null;
    private static $retArray = false;
    private static $unbuffered = false;
    private static $fullRowCount = false;

    public $num_rows = 0;
    public $num_fields = 0;
    private $last_result = null;
    private $querySource;

    /**
     * Datos para el objeto PDOStatement
     *
     * @var array
     */
    private $stData;

    /**
     * Comprobar que la base de datos existe.
     *
     * @return bool
     * @throws SPDatabaseException
     */
    public static function checkDatabaseExist()
    {
        try {
            $db = DBConnectionFactory::getFactory()->getConnection();

            $query = 'SELECT COUNT(*) '
                . 'FROM information_schema.tables '
                . 'WHERE table_schema=\'' . SP_Config::getValue("dbname") . '\' '
                . 'AND table_name = \'usrData\'';

            if ($db->query($query)->fetchColumn() !== 0) {
                return true;
            }
        } catch (PDOException $e) {
            throw new SPDatabaseException($e->getMessage(), $e->getCode());
        }

        return false;
    }

    /**
     * Obtener los datos para generar un select.
     *
     * @param string $tblName    con el nombre de la tabla a cunsultar
     * @param string $tblColId   con el nombre de la columna del tipo Id a mostrar
     * @param string $tblColName con el nombre de la columna del tipo Name a mostrar
     * @param array $arrFilter   con las columnas a filtrar
     * @param array $arrOrder    con el orden de las columnas
     * @return false|array con los valores del select con el Id como clave y el nombre como valor
     */
    public static function getValuesForSelect($tblName, $tblColId, $tblColName, $arrFilter = NULL, $arrOrder = NULL)
    {
        if (!$tblName || !$tblColId || !$tblColName) {
            return;
        }

        $strFilter = (is_array($arrFilter)) ? " WHERE " . implode(" OR ", $arrFilter) : "";
        $strOrder = (is_array($arrOrder)) ? " ORDER BY " . implode(",", $arrOrder) : 'ORDER BY ' . $tblColName . ' ASC';

        $query = "SELECT $tblColId, $tblColName FROM $tblName $strFilter $strOrder";

        self::setReturnArray();
        $queryRes = self::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        $arrValues = array();

        foreach ($queryRes as $row) {
            $arrValues[$row->$tblColId] = $row->$tblColName;
        }

        return $arrValues;
    }

    public static function setReturnArray()
    {
        self::$retArray = true;
    }

    /**
     * Obtener los resultados de una consulta.
     *
     * @param string $query       con la consulta a realizar
     * @param string $querySource con el nombre de la función que realiza la consulta
     * @param array $data        con los datos de la consulta
     * @return bool|array devuelve bool si hay un error. Devuelve array con el array de registros devueltos
     */
    public static function getResults($query, $querySource, &$data = null)
    {
        if (empty($query)) {
            self::resetVars();
            return false;
        }

        try {
            $db = new DB();
            $db->querySource = $querySource;
            $db->stData = $data;
            $doQuery = $db->doQuery($query, $querySource, self::$unbuffered);
        } catch (SPDatabaseException $e) {
            $db->logDBException($query, $e->getMessage(), $e->getCode());
            return false;
        }

        if (self::$unbuffered && is_object($doQuery) && get_class($doQuery) == "PDOStatement"){
            return $doQuery;
        }

        DB::$last_num_rows = (self::$fullRowCount === false) ? $db->num_rows : $db->getFullRowCount($query);

        if ($db->num_rows == 0) {
            self::resetVars();
            return false;
        }

        if ($db->num_rows == 1 && self::$retArray === false) {
            self::resetVars();
            return $db->last_result[0];
        }

        self::resetVars();
        return $db->last_result;
    }

    /**
     * Realizar una consulta a la BBDD.
     *
     * @param string $query       con la consulta a realizar
     * @param string $querySource con el nombre de la función que realiza la consulta
     * @param bool $unbuffered    realizar la consulta para obtener registro a registro
     * @return false|int devuelve bool si hay un error. Devuelve int con el número de registros
     * @throws SPDatabaseException
     */
    public function doQuery(&$query, $querySource, $unbuffered = false)
    {
        $isSelect = preg_match("/^(select|show)\s/i", $query);

        // Limpiar valores de caché y errores
        $this->last_result = array();

        try {
            $queryRes = $this->prepareQueryData($query);
        } catch (SPDatabaseException $e) {
            throw new SPDatabaseException($e->getMessage(), $e->getCode());
        }

        if ($isSelect) {
            if (!$unbuffered) {
                $this->num_fields = $queryRes->columnCount();
                $this->last_result = $queryRes->fetchAll(PDO::FETCH_OBJ);
            } else{
                return $queryRes;
            }

            $queryRes->closeCursor();

//            $this->num_rows = $this->getFullRowCount($query);
            $this->num_rows = count($this->last_result);

//            return $this->num_rows;
        }
    }

    /**
     * Asociar los parámetros de la consulta utilizando el tipo adecuado
     *
     * @param &$query
     * @param $isCount
     * @return bool
     * @throws SPDatabaseException
     */
    private function prepareQueryData(&$query, $isCount = false)
    {
        if ($isCount === true) {
            // No incluimos en el array de parámetros de posición los valores
            // utilizados para LIMIT
            preg_match_all('/(\?|:)/', $query, $count);

            // Indice a partir del cual no se incluyen valores
            $paramMaxIndex = (count($count[1]) > 0) ? count($count[1]) : 0;
        }

        try {
            $db = DBConnectionFactory::getFactory()->getConnection();

            if (is_array($this->stData)) {
                $sth = $db->prepare($query);
                $paramIndex = 0;

                foreach ($this->stData as $param => $value) {
                    // Si la clave es un número utilizamos marcadores de posición "?" en
                    // la consulta. En caso contrario marcadores de nombre
                    $param = (is_int($param)) ? $param + 1 : ':' . $param;

                    if ($isCount === true && count($count) > 0 && $paramIndex >= $paramMaxIndex) {
                        continue;
                    }

                    if ($param == 'blobcontent'){
                        $sth->bindValue($param, $value, PDO::PARAM_LOB);
                    } elseif (is_int($value)) {
                        //error_log("INT: " . $param . " -> " . $value);
                        $sth->bindValue($param, $value, PDO::PARAM_INT);
                    } else {
                        //error_log("STR: " . $param . " -> " . $value);
                        $sth->bindValue($param, $value, PDO::PARAM_STR);
                    }

                    $paramIndex++;
                }

                $sth->execute();
            } else {
                $sth = $db->query($query);
            }

            DB::$lastId = $db->lastInsertId();

            return $sth;
        } catch (PDOException $e) {
            error_log("Exception: " . $e->getMessage());
            throw new SPDatabaseException($e->getMessage());
        }

        return false;
    }

    /**
     * Obtener el número de filas de una consulta realizada
     *
     * @return int Número de files de la consulta
     * @throws SPDatabaseException
     */
    private function getFullRowCount(&$query)
    {
        if (empty($query)) {
            return 0;
        }

        $patterns = array(
            '/(LIMIT|ORDER BY|GROUP BY).*/i',
            '/SELECT DISTINCT\s([\w_]+),.* FROM/i',
            '/SELECT [\w_]+,.* FROM/i'
        );
        $replace = array('','SELECT COUNT(DISTINCT \1) FROM', 'SELECT COUNT(*) FROM');

        $query = preg_replace($patterns, $replace, $query);

        try {
            $db = DBConnectionFactory::getFactory()->getConnection();

            if (!is_array($this->stData)) {
                $queryRes = $db->query($query);
                $num = intval($queryRes->fetchColumn());
            } else {
                if ($queryRes = $this->prepareQueryData($query, true)) {
                    $num = intval($queryRes->fetchColumn());
                }
            }

            $queryRes->closeCursor();

            return $num;
        } catch (PDOException $e) {
            error_log("Exception: " . $e->getMessage());
            throw new SPDatabaseException($e->getMessage());
        }

        return 0;
    }

    /**
     * Método para registar los eventos de BD en el log
     *
     * @param $query
     * @param $errorMsg
     * @param $errorCode
     */
    private function logDBException($query, $errorMsg, $errorCode)
    {
        $message['action'] = $this->querySource;
        $message['text'][] = $errorMsg . '(' . $errorCode . ')';
        $message['text'][] = "SQL: " . DB::escape($query);

        error_log($query);
        error_log($errorMsg);
    }

    /**
     * Escapar una cadena de texto con funciones de mysqli.
     *
     * @param string $str con la cadena a escapar
     * @return string con la cadena escapada
     */
    public static function escape($str)
    {
        try {
            $db = DBConnectionFactory::getFactory()->getConnection();

            return $db->quote(trim($str));
        } catch (SPDatabaseException $e) {
            return $str;
        }
    }

    /**
     * Realizar una consulta y devolver el resultado sin datos
     *
     * @param $query
     * @param $querySource
     * @param bool $unbuffered
     * @return bool
     */
    public static function getQuery($query, $querySource, &$data = null, $unbuffered = false)
    {
        if (empty($query)) {
            return false;
        }

        try {
            $db = new DB();
            $db->querySource = $querySource;
            $db->stData = $data;
            $db->doQuery($query, $querySource, $unbuffered);
            DB::$last_num_rows = $db->num_rows;
        } catch (SPDatabaseException $e) {
            $db->logDBException($query, $e->getMessage(), $e->getCode());

            DB::$txtError = $e->getMessage();
            DB::$numError = $e->getCode();

            return false;
        }

        return true;
    }

    public static function setUnbuffered($on = true)
    {
        self::$unbuffered = (bool)$on;
    }

    public static function setFullRowCount()
    {
        self::$fullRowCount = true;
    }

    private static function resetVars()
    {
        self::$unbuffered = false;
        self::$fullRowCount = false;
        self::$retArray = false;
    }

    /**
     * Establecer los parámetos de la consulta preparada
     *
     * @param &$data array Con los datos de los parámetros de la consulta
     */
    public function setParamData(&$data)
    {
        $this->stData = $data;
    }
}
