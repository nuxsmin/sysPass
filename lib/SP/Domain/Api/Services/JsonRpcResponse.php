<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Api\Services;

use Exception;
use SP\Domain\Core\Exceptions\SPException;
use SP\Http\Json;

/**
 * Class JsonRpcResponse
 *
 * @package SP\Api
 */
final class JsonRpcResponse
{
    public const PARSE_ERROR      = -32700;
    public const INVALID_REQUEST  = -32600;
    public const METHOD_NOT_FOUND = -32601;
    public const INVALID_PARAMS   = -32602;
    public const INTERNAL_ERROR   = -32603;
    public const SERVER_ERROR     = -32000;

    /**
     * @param  ApiResponse  $apiResponse
     * @param  int  $id
     *
     * @return string
     * @throws SPException
     */
    public static function getResponse(
        ApiResponse $apiResponse,
        int $id
    ): string {
        return Json::getJson([
            'jsonrpc' => '2.0',
            'result'  => $apiResponse->getResponse(),
            'id'      => $id,
        ], JSON_UNESCAPED_SLASHES);
    }

    public static function getResponseException(Exception $e, int $id): string
    {
        $data = ($e instanceof SPException) ? $e->getHint() : null;

        return self::getResponseError($e->getMessage(), $e->getCode(), $id, $data);
    }

    /**
     * @param  string  $message
     * @param  int  $code
     * @param  int  $id
     * @param  mixed|null  $data
     *
     * @return string
     */
    public static function getResponseError(
        string $message,
        int $code,
        int $id,
        $data = null
    ): string {
        return json_encode([
            'jsonrpc' => '2.0',
            'error'   => [
                'message' => __($message),
                'code'    => $code,
                'data'    => $data,
            ],
            'id'      => $id,
        ], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
