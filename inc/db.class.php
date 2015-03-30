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
 * Esta clase es la encargada de realizar las operaciones con la BBDD de sysPass.
 */
class DB
{
    static $last_result;
    static $affected_rows;
    static $lastId;
    static $txtError;
    static $numError;
    static $num_rows;
    static $num_fields;
    private static $_db;

    /**
     * Comprobar que la base de datos existe.
     *
     * @return bool
     */
    public static function checkDatabaseExist()
    {
        if (!self::connection()) {
            return false;
        }

        $query = 'SELECT COUNT(*) '
            . 'FROM information_schema.tables'
            . " WHERE table_schema='" . SP_Config::getValue("dbname") . "' "
            . "AND table_name = 'usrData';";

        $resquery = self::$_db->query($query);

        if ($resquery) {
            $row = $resquery->fetch_row();
        }

        if (!$resquery || $row[0] == 0) {
            return false;
        }

        return true;
    }

    /**
     * Realizar la conexión con la BBDD.
     * Esta función utiliza mysqli para conectar con la base de datos.
     * Guarda el objeto creado en la variable $_db de la clase
     *
     * @return bool
     */
    private static function connection()
    {
        if (is_object(self::$_db)) {
            return true;
        }

        $isInstalled = SP_Config::getValue('installed');

        $dbhost = SP_Config::getValue('dbhost');
        $dbuser = SP_Config::getValue('dbuser');
        $dbpass = SP_Config::getValue('dbpass');
        $dbname = SP_Config::getValue('dbname');
        $dbport = SP_Config::getValue('dbport', 3306);

        if (empty($dbhost) || empty($dbuser) || empty($dbpass) || empty($dbname)) {
            if ($isInstalled) {
                SP_Init::initError(_('No es posible conectar con la BD'), _('Compruebe los datos de conexión'));
            } else {
                return false;
            }
        }

        self::$_db = @new mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);

        if (!is_object(self::$_db) || self::$_db->connect_errno) {
            if ($isInstalled) {
                if (self::$_db->connect_errno === 1049) {
                    SP_Config::setValue('installed', '0');
                }

                SP_Init::initError(_('No es posible conectar con la BD'), 'Error ' . self::$_db->connect_errno . ': ' . self::$_db->connect_error);
            } else {
                return false;
            }
        }

        if (!self::$_db->set_charset("utf8")) {
            SP_Init::initError(_('No es posible conectar con la BD'), 'Error ' . self::$_db->connect_errno . ': ' . self::$_db->connect_error);
        }

        return true;
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
        $queryRes = self::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        $arrValues = array();

        foreach ($queryRes as $row) {
            $arrValues[$row->$tblColId] = $row->$tblColName;
        }

        return $arrValues;
    }

    /**
     * Obtener los resultados de una consulta.
     *
     * @param string $query con la consulta a realizar
     * @param string $querySource con el nombre de la función que realiza la consulta
     * @param bool $retArray   devolver un array si la consulta tiene esultados
     * @param bool $unbuffered devolver el resultado registro a registro
     * @return bool|array devuelve bool si hay un error. Devuelve array con el array de registros devueltos
     */
    public static function getResults($query, $querySource, $retArray = false, $unbuffered = false)
    {
        if ($query) {
            self::doQuery($query, $querySource, $unbuffered);
        }

        if (self::$numError || self::$num_rows === 0) {
            return false;
        }

        if (is_null(self::$numError) && count(self::$last_result) === 0) {
            return true;
        }

        if ($retArray === true && is_object(self::$last_result)) {
            return array(self::$last_result);
        }

        return self::$last_result;
    }

    /**
     * Realizar una consulta a la BBDD.
     *
     * @param string $query       con la consulta a realizar
     * @param string $querySource con el nombre de la función que realiza la consulta
     * @param bool $unbuffered    realizar la consulta para obtener registro a registro
     * @return false|int devuelve bool si hay un error. Devuelve int con el número de registros
     */
    public static function doQuery($query, $querySource, $unbuffered = false)
    {
        if (!self::connection() || !is_object(self::$_db)) {
            return false;
        }

        $isSelect = preg_match("/^.*(select|show)\s/i", $query);

        // Limpiar valores de caché y errores
        self::$last_result = array();
        self::$numError = 0;
        self::$txtError = '';

        // Comprobamos si la consulta debe de ser devuelta completa o por registro
        if (!$unbuffered) {
            $queryRes = self::$_db->query($query);
        } else {
            $queryRes = self::$_db->real_query($query);
        }

        if (!$queryRes) {
            self::$numError = self::$_db->errno;
            self::$txtError = self::$_db->error;

            $message['action'] = $querySource;
            $message['text'][] = self::$_db->error . '(' . self::$_db->errno . ')';
            $message['text'][] = "SQL: " . self::escape($query);

            SP_Log::wrLogInfo($message);
            return false;
        }

        if ($isSelect) {
            //self::$num_rows = $queryRes->num_rows;
            self::$num_rows = self::$_db->affected_rows;

            if (!$unbuffered) {
                self::$num_fields = self::$_db->field_count;

                if (self::$num_rows === 1) {
                    self::$last_result = @$queryRes->fetch_object();
                } else {
                    $num_row = 0;

                    while ($row = @$queryRes->fetch_object()) {
                        self::$last_result[$num_row] = $row;
                        $num_row++;
                    }
                }

                $queryRes->close();
            } else {
                self::$last_result = self::$_db->use_result();
            }
        }

        self::$lastId = self::$_db->insert_id;

        return self::$num_rows;
    }

    /**
     * Escapar una cadena de texto con funciones de mysqli.
     *
     * @param string $str con la cadena a escapar
     * @return string con la cadena escapada
     */
    public static function escape($str)
    {
        if (self::connection()) {
            return self::$_db->real_escape_string(trim($str));
        } else {
            return $str;
        }
    }
}
