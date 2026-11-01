<?php

declare(strict_types=1);
/*
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

namespace SP\Modules\Api\Adapters;

use Closure;
use Exception;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__;

/**
 * Maps a Domain ActionResponse to the API JSON-RPC and REST envelopes.
 */
final class ActionResponseAdapter
{
    public const PARSE_ERROR      = -32700;
    public const INVALID_REQUEST  = -32600;
    public const METHOD_NOT_FOUND = -32601;
    public const INVALID_PARAMS   = -32602;
    public const INTERNAL_ERROR   = -32603;
    public const SERVER_ERROR     = -32000;

    /**
     * @throws SPException
     */
    public function toJsonRpc(ActionResponse $actionResponse, int $id): string
    {
        return Serde::serializeJson(
            [
                'jsonrpc' => '2.0',
                'result' => $this->buildResult($actionResponse),
                'id' => $id,
            ],
            JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @throws SPException
     */
    public function toRest(ActionResponse $actionResponse): string
    {
        return Serde::serializeJson($this->buildResult($actionResponse), JSON_UNESCAPED_SLASHES);
    }

    public function toJsonRpcException(Exception $e, int $id): string
    {
        $data = ($e instanceof SPException && $e->getHint() !== null)
            ? ['help' => explode(PHP_EOL, $e->getHint())]
            : null;

        return $this->toJsonRpcError($e->getMessage(), $e->getCode(), $id, $data);
    }

    public function toJsonRpcError(string $message, int $code, int $id, mixed $data = null): string
    {
        return (string)json_encode(
            [
                'jsonrpc' => '2.0',
                'error' => [
                    'message' => __($message),
                    'code' => $code,
                    'data' => $data,
                ],
                'id' => $id,
            ],
            JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }

    /**
     * Builds the result envelope used by both output formats.
     *
     * @return array{itemId: int|null, result: mixed, resultCode: int, resultMessage: string|null, count: int|null}
     */
    private function buildResult(ActionResponse $actionResponse): array
    {
        $result = $actionResponse->extra
            ?? ($actionResponse->subject instanceof Closure ? null : $actionResponse->subject);

        return [
            'itemId' => $actionResponse->itemId,
            'result' => $result,
            'resultCode' => $actionResponse->status === ResponseStatus::OK ? 0 : 1,
            'resultMessage' => is_string($actionResponse->subject) && $actionResponse->subject !== ''
                ? __($actionResponse->subject)
                : null,
            'count' => is_array($result) ? count($result) : null,
        ];
    }
}
