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

use SP\Core\Exceptions\SPException;
use SP\Http\Json;

/**
 * Class JsonRpcResponse
 *
 * @package SP\Api
 */
final class JsonRpcResponse
{
    /**
     * @param ApiResponse $apiResponse
     * @param             $id
     *
     * @return string
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function getResponse(ApiResponse $apiResponse, $id)
    {
        return Json::getJson([
            'jsonrpc' => '2.0',
            'result' => $apiResponse->getResponse(),
            'id' => $id
        ]);
    }

    /**
     * @param \Exception $e
     * @param            $id
     *
     * @return string
     */
    public static function getResponseException(\Exception $e, $id)
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'error' => [
                'message' => __($e->getMessage()),
                'code' => $e->getCode(),
                'data' => ($e instanceof SPException) ? $e->getHint() : null
            ],
            'id' => $id
        ], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}