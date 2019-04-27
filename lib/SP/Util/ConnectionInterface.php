<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Util;

/**
 * Class ConnectionInterface
 *
 * @package SP\Util
 */
interface ConnectionInterface
{
    const TYPE_TCP = 1;
    const TYPE_UDP = 2;
    const SOCKET_TIMEOUT = 10;

    /**
     * Obtener un socket
     *
     * @param $type
     *
     * @return resource
     */
    public function getSocket($type);

    /**
     * Cerrar un socket
     *
     * @return mixed
     */
    public function closeSocket();

    /**
     * Obtener el último error del socket
     *
     * @return string
     */
    public function getSocketError();

    /**
     * Enviar un mensaje al socket
     *
     * @param $message string El mensaje a enviar
     *
     * @return mixed
     */
    public function send($message);
}