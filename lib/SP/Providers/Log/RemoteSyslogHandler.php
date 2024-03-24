<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Providers\Log;

use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

/**
 * Class RemoteSyslogHandler
 *
 * @package SP\Providers\Log
 */
final class RemoteSyslogHandler extends LoggerBase
{
    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEventsString(): string
    {
        return $this->events;
    }


    public function initialize(): void
    {
        $configData = $this->config->getConfigData();

        $this->logger->pushHandler(
            new SyslogUdpHandler(
                $configData->getSyslogServer(),
                $configData->getSyslogPort(),
                LOG_USER,
                Logger::DEBUG,
                true,
                'syspass'
            )
        );

        parent::initialize();
    }
}
