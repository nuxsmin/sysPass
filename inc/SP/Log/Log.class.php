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

namespace SP\Log;

use SP\Config\Config;
use SP\Storage\DB;
use SP\Core\Session;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de manejar el registro de eventos
 */
class Log extends ActionLog
{
    /**
     * @var int
     */
    public static $numRows = 0;

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
            'FROM_UNIXTIME(log_date) AS log_date,' .
            'log_action,' .
            'log_level,' .
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

        self::writeNewLogAndEmail(_('Vaciar Eventos'), _('Vaciar registro de eventos'), null);

        return true;
    }

    /**
     * Obtener una nueva instancia de la clase inicializada
     *
     * @param string $action      La acción realizada
     * @param string $description La descripción de la acción realizada
     * @param string $level
     * @return Log
     */
    public static function writeNewLogAndEmail($action, $description = null, $level = Log::INFO)
    {
        $Log = new Log($action, $description, $level);
        $Log->writeLog();

        Email::sendEmail($Log);

        return $Log;
    }

    /**
     * Escribir un nuevo evento en el registro de eventos
     *
     * @param bool $resetDescription Restablecer la descripción
     * @return bool
     */
    public function writeLog($resetDescription = false)
    {
        if (defined('IS_INSTALLER') && IS_INSTALLER === 1) {
            error_log('Action: ' . $this->getAction() . ' -- Description: ' . $this->getDescription());
        }

        if (!Checks::logIsEnabled()) {
            return false;
        }

        if (Checks::syslogIsEnabled()){
            $this->sendToSyslog();
        }

        $description = trim($this->getDescription() . self::NEWLINE_TXT . $this->getDetails(), ';');

        $query = 'INSERT INTO log SET ' .
            'log_date = UNIX_TIMESTAMP(),' .
            'log_login = :login,' .
            'log_userId = :userId,' .
            'log_ipAddress = :ipAddress,' .
            'log_action = :action,' .
            'log_level = :level,' .
            'log_description = :description';

        $data['login'] = Session::getUserLogin();
        $data['userId'] = Session::getUserId();
        $data['ipAddress'] = $_SERVER['REMOTE_ADDR'];
        $data['action'] = $this->getAction();
        $data['level'] = $this->getLogLevel();
        $data['description'] = $description;

        if ($resetDescription === true) {
            $this->resetDescription();
        }

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Enviar mensaje al syslog
     */
    private function sendToSyslog()
    {
        $msg = 'CEF:0|sysPass|logger|' . implode('.', Util::getVersion(true)) . '|';
        $msg .= $this->getAction() . '|';
        $msg .= $this->getDescription() . '|';
        $msg .= '0|';
        $msg .= sprintf('ip_addr="%s" user_name="%s"', $_SERVER['REMOTE_ADDR'], Session::getUserLogin());

        $Syslog = new Syslog();
        $Syslog->setIsRemote(Checks::remoteSyslogIsEnabled());
        $Syslog->info($msg);
    }

    /**
     * Obtener una nueva instancia de la clase inicializada
     *
     * @param string $action      La acción realizada
     * @param string $description La descripción de la acción realizada
     * @param string $level
     * @return Log
     */
    public static function newLog($action, $description = null, $level = Log::INFO)
    {
        return new Log($action, $description, $level);
    }

    /**
     * Escribir un nuevo evento en el registro de eventos
     *
     * @param string $action      La acción realizada
     * @param string $description La descripción de la acción realizada
     * @param string $level
     * @return Log
     */
    public static function writeNewLog($action, $description = null, $level = Log::INFO)
    {
        $Log = new Log($action, $description, $level);
        $Log->writeLog();

        return $Log;
    }
}