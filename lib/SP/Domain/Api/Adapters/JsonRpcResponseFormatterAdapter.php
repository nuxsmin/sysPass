<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Api\Adapters;

use Exception;
use SP\Domain\Api\Dtos\ApiResponse;
use SP\Domain\Api\Ports\JsonRpcResponseFormatter;
use SP\Domain\Api\Services\JsonRpcResponse;

/**
 * Adapter for the JSON-RPC response implementation.
 */
final class JsonRpcResponseFormatterAdapter implements JsonRpcResponseFormatter
{
    public function getResponse(ApiResponse $apiResponse, int $id): string
    {
        return JsonRpcResponse::getResponse($apiResponse, $id);
    }

    public function getResponseException(Exception $e, int $id): string
    {
        return JsonRpcResponse::getResponseException($e, $id);
    }

    public function getResponseError(string $message, int $code, int $id, mixed $data = null): string
    {
        return JsonRpcResponse::getResponseError($message, $code, $id, $data);
    }
}
