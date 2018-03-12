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

namespace SP\Modules\Api\Controllers\Traits;

use Klein\Klein;
use SP\Api\ApiResponse;
use SP\Api\JsonRpcResponse;
use SP\Core\Exceptions\SPException;

/**
 * Trait ResponseTrait
 * @package SP\Modules\Api\Controllers\Traits
 * @property Klein $router
 */
trait ResponseTrait
{
    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * {"jsonrpc": "2.0", "result": 19, "id": 3}
     *
     * @param ApiResponse $apiResponse
     * @param int $id
     * @return string La cadena en formato JSON
     */
    protected function returnResponse(ApiResponse $apiResponse, $id = 0)
    {
        $this->router->response()->headers()->set('Content-type', 'application/json; charset=utf-8');

        try {
            exit(JsonRpcResponse::getResponse($apiResponse, $id));
        } catch (SPException $e) {
            processException($e);

            exit(JsonRpcResponse::getResponseException($e, $id));
        }
    }

    /**
     * @param \Exception $e
     * @param int $id
     * @return string
     */
    protected function returnResponseException(\Exception $e, $id = 0)
    {
        $this->router->response()->headers()->set('Content-type', 'application/json; charset=utf-8');

        exit(JsonRpcResponse::getResponseException($e, $id));
    }
}