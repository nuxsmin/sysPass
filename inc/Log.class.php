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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de manejar el registro de eventos
 */
class Log extends ActionLog
{
    static $numRows;

    /**
     * Obtener los eventos guardados.
     *
     * @param int $start con el número de registro desde el que empezar
     * @return false|array con el resultado de la consulta
     */
    public static function getEvents($start)
    {
        $query = 'SELECT ' .
            'log_id,' .
            'FROM_UNIXTIME(log_date) as log_date,' .
            'log_action,' .
            'log_login,' .
            'log_ipAddress,' .
            'log_description ' .
            'FROM log ' .
            'ORDER BY log_id DESC ' .
            'LIMIT :start, 50';

        $data['start'] = $start;

        // Obtenemos el número total de registros
        DB::setFullRowCount();

        // Devolver un array siempre
        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        self::$numRows = DB::$lastNumRows;

        return $queryRes;
    }

    /**
     * Limpiar el registro de eventos.
     *
     * @return bool con el resultado
     */
    public static function clearEvents()
    {
        $query = 'TRUNCATE TABLE log';

        if (DB::getQuery($query, __FUNCTION__) === false) {
            return false;
        }

        self::writeNewLogAndEmail(_('Vaciar Eventos'), _('Vaciar registro de eventos'));

        return true;
    }

    /**
     * Obtener una nueva instancia de la clase inicializada
     *
     * @param      $action string La acción realizada
     * @param null $description string La descripción de la acción realizada
     * @return Log
     */
    public static function newLog($action, $description = null)
    {
        return new Log($action, $description);
    }

    /**
     * Escribir un nuevo evento en el registro de eventos
     *
     * @param      $action string La acción realizada
     * @param null $description string La descripción de la acción realizada
     * @return Log
     */
    public static function writeNewLog($action, $description = null){
        $log = new Log($action, $description);
        $log->writeLog();

        return $log;
    }

    /**
     * Obtener una nueva instancia de la clase inicializada
     *
     * @param      $action string La acción realizada
     * @param null $description string La descripción de la acción realizada
     * @return Log
     */
    public static function writeNewLogAndEmail($action, $description = null){
        $log = new Log($action, $description);
        $log->writeLog();

        Email::sendEmail($log);

        return $log;
    }

    /**
     * Escribir un nuevo evento en el registro de eventos
     *
     * @return bool
     */
    public function writeLog(){
        if (defined('IS_INSTALLER') && IS_INSTALLER === 1) {
            error_log('Action: ' . $this->getAction() . ' -- Description: ' . $this->getDescription());
        }

        if (!Util::logIsEnabled()) {
            return false;
        }

        $query = 'INSERT INTO log SET ' .
            'log_date = UNIX_TIMESTAMP(),' .
            'log_login = :login,' .
            'log_userId = :userId,' .
            'log_ipAddress = :ipAddress,' .
            'log_action = :action,' .
            'log_description = :description';

        $data['login'] = Session::getUserLogin();
        $data['userId'] = Session::getUserId();
        $data['ipAddress'] = $_SERVER['REMOTE_ADDR'];
        $data['action'] = $this->getAction();
        $data['description'] = $this->getDescription();

        return DB::getQuery($query, __FUNCTION__, $data);
    }
}