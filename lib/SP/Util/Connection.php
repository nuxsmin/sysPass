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

use SP\Core\Exceptions\SPException;

/**
 * Class Connection para crear conexiones TCP o UDP
 *
 * @package SP\Util
 */
final class Connection implements ConnectionInterface
{
    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var int
     */
    protected $port = 0;
    /**
     * Código de error del socket
     *
     * @var int
     */
    protected $errorno = 0;
    /**
     * Mensaje de error del socket
     *
     * @var string
     */
    protected $errorstr = '';

    /**
     * @param $host string El host a conectar
     * @param $port string El puerto a conectar
     */
    public function __construct($host, $port)
    {
        $this->host = gethostbyname($host);
        $this->port = $port;
    }

    /**
     * Obtener un socket
     *
     * @param $type int EL tipo de socket TCP/UDP
     *
     * @return resource
     * @throws SPException
     */
    public function getSocket($type)
    {
        switch ($type) {
            case self::TYPE_TCP:
                $this->socket = $this->getTCPSocket();
                break;
            case self::TYPE_UDP:
                $this->socket = $this->getUDPSocket();
                break;
            default:
                $this->socket = $this->getTCPSocket();
                break;
        }

        if ($this->socket === false) {
            throw new SPException($this->getSocketError(), SPException::WARNING);
        }

        stream_set_timeout($this->socket, self::SOCKET_TIMEOUT);

        return $this->socket;
    }

    /**
     * Obtener un socket del tipo TCP
     *
     * @return resource
     */
    private function getTCPSocket()
    {
        return stream_socket_client('tcp://' . $this->host . ':' . $this->port, $this->errorno, $this->errorstr, self::SOCKET_TIMEOUT);
//        return @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    }

    /**
     * Obtener un socket del tipo UDP
     *
     * @return resource
     */
    private function getUDPSocket()
    {
        return stream_socket_client('udp://' . $this->host . ':' . $this->port, $this->errorno, $this->errorstr, self::SOCKET_TIMEOUT);
//        return @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    /**
     * Obtener el último error del socket
     *
     * @return string
     */
    public function getSocketError()
    {
        return sprintf('%s (%d)', $this->errorstr, $this->errorno);
//        return socket_strerror(socket_last_error($this->_socket));
    }

    /**
     * Cerrar el socket
     */
    public function closeSocket()
    {
        fclose($this->socket);
//        @socket_close($this->_socket);
    }

    /**
     * Enviar un mensaje al socket
     *
     * @param $message string El mensaje a enviar
     *
     * @return int|bool
     * @throws SPException
     */
    public function send($message)
    {
        if (!is_resource($this->socket)) {
            throw new SPException(__u('Socket not initialized'), SPException::WARNING);
        }

        $nBytes = @fwrite($this->socket, $message);
//        $nBytes = @socket_sendto($this->_socket, $message, strlen($message), 0, $this->_host, $this->port);

        if ($nBytes === false) {
            throw new SPException(__u('Error while sending the data'), SPException::WARNING, $this->getSocketError());
        }

        return $nBytes;
    }
}