<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Api;

use SP\Core\DataCollection;
use SP\Services\ServiceException;

/**
 * Class ApiRequest
 *
 * @package SP\Services\Api
 */
class ApiRequest extends DataCollection
{
    /**
     * @var string
     */
    protected $method;
    /**
     * @var int
     */
    protected $id;

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Obtener los datos de la petición
     *
     * Comprueba que el JSON esté bien formado
     *
     * @throws ServiceException
     */
    public function getRequestData()
    {
        if (($request = file_get_contents('php://input')) === false
            || ($data = json_decode($request, true)) === null
        ) {
            throw new ServiceException(
                __u('Datos inválidos'),
                ServiceException::ERROR,
                null,
                -32700
            );
        }

        if (!isset($data['jsonrpc'], $data['method'], $data['params'], $data['id'], $data['params']['authToken'])) {
            throw new ServiceException(
                __u('Fomato incorrecto'),
                ServiceException::ERROR,
                null,
                -32600
            );
        }

        $this->method = preg_replace('#[^a-z/]+#i', '', $data['method']);
        $this->id = filter_var($data['id'], FILTER_VALIDATE_INT);
        $this->attributes = $data['params'];

        return $this;
    }
}