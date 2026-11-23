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

namespace SP\Modules\Api\Adapters;

use JsonException;
use SP\Domain\Api\Dtos\ApiRequestData;
use SP\Domain\Api\Enums\JsonRpcErrorCode;
use SP\Domain\Api\Exceptions\ApiRequestException;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__u;

/**
 * Builds an API request from the incoming JSON-RPC payload.
 */
final class ApiRequestAdapter implements ApiRequestService
{
    protected ?string                 $method       = null;
    protected int|float|string|null   $id           = null;
    protected bool                    $notification = false;
    protected ?ApiRequestData         $data         = null;

    private function __construct()
    {
    }

    /**
     * Build the ApiRequest from the request itself.
     *
     * It will read the 'php://input' strean and get the contents into a JSON format
     *
     * @param string $stream
     *
     * @return ApiRequestService
     * @throws ApiRequestException
     */
    public static function buildFromRequest(string $stream = self::PHP_REQUEST_STREAM): ApiRequestService
    {
        $content = file_get_contents($stream);

        if (empty($content)) {
            throw new ApiRequestException(
                __u('Invalid data'),
                SPException::ERROR,
                null,
                JsonRpcErrorCode::PARSE_ERROR
            );
        }

        return self::buildFromJson($content);
    }

    /**
     * Build the ApiRequest from a JSON data structure.
     *
     * @param string $json
     *
     * @return ApiRequestService
     * @throws ApiRequestException
     */
    private static function buildFromJson(string $json): ApiRequestService
    {
        try {
            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new ApiRequestException(
                __u('Invalid data'),
                SPException::ERROR,
                null,
                JsonRpcErrorCode::PARSE_ERROR,
                $e
            );
        }

        if (!is_array($data)) {
            throw new ApiRequestException(
                __u('Invalid format'),
                SPException::ERROR,
                null,
                JsonRpcErrorCode::INVALID_REQUEST
            );
        }

        $hasId = array_key_exists('id', $data);
        $rawId = $hasId ? $data['id'] : null;

        if ($hasId && !is_int($rawId) && !is_float($rawId) && !is_string($rawId) && $rawId !== null) {
            throw new ApiRequestException(
                __u('Invalid format'),
                SPException::ERROR,
                null,
                JsonRpcErrorCode::INVALID_REQUEST
            );
        }

        $isNotification = !$hasId
            && ($data['jsonrpc'] ?? null) === '2.0'
            && is_string($data['method'] ?? null)
            && $data['method'] !== '';

        if (!isset(
            $data['jsonrpc'],
            $data['method'],
            $data['params']['authToken']
        )
            || $data['jsonrpc'] !== '2.0'
            || !is_string($data['method'])
            || $data['method'] === ''
            || !is_array($data['params'])
        ) {
            throw (new ApiRequestException(
                __u('Invalid format'),
                SPException::ERROR,
                null,
                JsonRpcErrorCode::INVALID_REQUEST
            ))
                ->withRequestId($rawId)
                ->asNotification($isNotification);
        }

        $apiRequest = new self();
        $apiRequest->method = preg_replace('#[^a-z/]+#i', '', $data['method']);
        $apiRequest->id = $rawId;
        $apiRequest->notification = !$hasId;
        $apiRequest->data = new ApiRequestData($data['params']);

        return $apiRequest;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        return $this->data->exists($key);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return int|float|string|null
     */
    public function getId(): int|float|string|null
    {
        return $this->id;
    }

    public function isNotification(): bool
    {
        return $this->notification;
    }
}
