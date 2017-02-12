<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Util\Connection;

/**
 * Class Syslog para envío de mensaje al servicio de syslog
 *
 * @package SP\Log
 */
class Syslog extends AbstractLogger
{
    /**
     * @var bool
     */
    private $isRemote = false;

    /**
     * @param boolean $isRemote
     */
    public function setIsRemote($isRemote)
    {
        $this->isRemote = $isRemote;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if ($this->isRemote === false) {
            openlog("sysPass", LOG_PID, LOG_LOCAL0);
            syslog($this->getSyslogLevel($level), $message);
            closelog();
        } else {
            $this->logRemote($message);
        }
    }

    /**
     * Devolver el código de nivel para la syslog
     *
     * @param $level string El nivel del mensaje
     * @return int
     */
    private function getSyslogLevel($level)
    {
        switch ($level) {
            case LogLevel::EMERGENCY:
                return LOG_EMERG;
            case LogLevel::ALERT:
                return LOG_ALERT;
            case LogLevel::CRITICAL:
                return LOG_CRIT;
            case LogLevel::ERROR:
                return LOG_ERR;
            case LogLevel::WARNING:
                return LOG_WARNING;
            case LogLevel::NOTICE:
                return LOG_NOTICE;
            case LogLevel::INFO:
                return LOG_INFO;
            case LogLevel::DEBUG:
                return LOG_DEBUG;
        }
    }

    /**
     * Enviar un mensaje a syslog remoto
     *
     * @param $message
     */
    private function logRemote($message)
    {
        $server = Config::getConfig()->getSyslogServer();
        $port = Config::getConfig()->getSyslogPort();

        if (!empty($server)) {
            $syslogMsg = date('M d H:i:s ') . "sysPass web: $message";

            try {
                $Connecion = new Connection($server, $port);
                $Connecion->getSocket(Connection::TYPE_UDP);
                $Connecion->send($syslogMsg);
                $Connecion->closeSocket();
            } catch (SPException $e) {
                error_log($e->getMessage());
                error_log($e->getHint());
            }
        }
    }
}