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

namespace SP\Log;

use SP\Core\DiFactory;
use SP\Core\Language;
use SP\Core\Messages\LogMessage;
use SP\Core\Session;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die();

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
     * @param int $count Número de registros por consulta
     * @return array|false con el resultado de la consulta
     */
    public static function getEvents($start, $count)
    {
        $Data = new QueryData();
        $Data->setSelect('log_id,FROM_UNIXTIME(log_date) AS log_date,log_action,log_level,log_login,log_ipAddress,log_description');
        $Data->setFrom('log');
        $Data->setOrder('log_id DESC');
        $Data->setLimit('?, ?');
        $Data->addParam($start);
        $Data->addParam($count);

        // Obtenemos el número total de registros
        DB::setFullRowCount();

        $queryRes = DB::getResultsArray($Data);

        self::$numRows = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Limpiar el registro de eventos.
     *
     * @return bool con el resultado
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function clearEvents()
    {
        $query = 'TRUNCATE TABLE log';

        $Data = new QueryData();
        $Data->setQuery($query);

        if (DB::getQuery($Data) === false) {
            return false;
        }

        self::writeNewLogAndEmail(__('Vaciar Eventos', false), __('Vaciar registro de eventos', false), null);

        return true;
    }

    /**
     * Obtener una nueva instancia de la clase inicializada
     *
     * @param string $action      La acción realizada
     * @param string $description La descripción de la acción realizada
     * @param string $level
     * @return Log
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function writeNewLogAndEmail($action, $description = null, $level = Log::INFO)
    {
        $Log = Log::writeNewLog($action, $description, $level);
        Email::sendEmail($Log->getLogMessage());

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
        if ((defined('IS_INSTALLER') && IS_INSTALLER === 1)
            || DiFactory::getDBStorage()->getDbStatus() === 1
        ) {
            debugLog('Action: ' . $this->LogMessage->getAction() . ' -- Description: ' . $this->LogMessage->getDescription() . ' -- Details: ' . $this->LogMessage->getDetails());

            return false;
        }

        if (!Checks::logIsEnabled()) {
            return false;
        }

        Language::setAppLocales();

        if (Checks::syslogIsEnabled()) {
            $this->sendToSyslog();
        }

        $description = trim($this->LogMessage->getDescription(true) . PHP_EOL . $this->LogMessage->getDetails(true));

        $query = 'INSERT INTO log SET ' .
            'log_date = UNIX_TIMESTAMP(),' .
            'log_login = :login,' .
            'log_userId = :userId,' .
            'log_ipAddress = :ipAddress,' .
            'log_action = :action,' .
            'log_level = :level,' .
            'log_description = :description';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Session::getUserData()->getUserLogin(), 'login');
        $Data->addParam(Session::getUserData()->getUserId(), 'userId');
        $Data->addParam($_SERVER['REMOTE_ADDR'], 'ipAddress');
        $Data->addParam(utf8_encode($this->LogMessage->getAction(true)), 'action');
        $Data->addParam($this->getLogLevel(), 'level');
        $Data->addParam(utf8_encode($description), 'description');

        if ($resetDescription === true) {
            $this->LogMessage->resetDescription();
        }

        $query = DB::getQuery($Data);

        Language::unsetAppLocales();

        return $query;
    }

    /**
     * Enviar mensaje al syslog
     */
    private function sendToSyslog()
    {
        $description = trim($this->LogMessage->getDescription(true) . PHP_EOL . $this->LogMessage->getDetails(true));

        $msg = 'CEF:0|sysPass|logger|' . implode('.', Util::getVersion(true)) . '|';
        $msg .= $this->LogMessage->getAction(true) . '|';
        $msg .= $description . '|';
        $msg .= '0|';
        $msg .= sprintf('ip_addr="%s" user_name="%s"', $_SERVER['REMOTE_ADDR'], Session::getUserData()->getUserLogin());

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
        $LogMessage = new LogMessage();
        $LogMessage->setAction($action);

        if ($description !== null) {
            $LogMessage->addDescription($description);
        }

        return new Log($LogMessage, $level);
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
        $LogMessage = new LogMessage();
        $LogMessage->setAction($action);
        $LogMessage->addDescription($description);

        $Log = new Log($LogMessage, $level);
        $Log->writeLog();

        return $Log;
    }
}