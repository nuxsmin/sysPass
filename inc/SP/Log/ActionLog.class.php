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

use SP\Core\Messages\LogMessage;

/**
 * Clase abstracta ActionLog para la gestión de mensajes de eventos
 *
 * @package SP
 */
abstract class ActionLog extends LogLevel
{
    /**
     * Constante de nueva línea para descripciones
     */
    const NEWLINE_TXT = PHP_EOL;
    /**
     * Constante de nueva línea para descripciones en formato HTML
     */
    const NEWLINE_HTML = '<br>';
    /**
     * @var string
     */
    protected $logLevel = '';
    /**
     * @var LogMessage
     */
    protected $LogMessage;

    /**
     * Contructor
     *
     * @param LogMessage $LogMessage
     * @param string     $level El nivel del mensaje
     */
    public function __construct(LogMessage $LogMessage = null, $level = Log::INFO)
    {
        $this->LogMessage = $LogMessage ?: new LogMessage();
        $this->logLevel = $level;
    }

    /**
     * @return string
     */
    public function getLogLevel()
    {
        return strtoupper($this->logLevel);
    }

    /**
     * @param string $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * @return LogMessage
     */
    public function getLogMessage()
    {
        return $this->LogMessage;
    }

    /**
     * @param LogMessage $LogMessage
     */
    public function setLogMessage(LogMessage $LogMessage)
    {
        $this->LogMessage = $LogMessage;
    }
}