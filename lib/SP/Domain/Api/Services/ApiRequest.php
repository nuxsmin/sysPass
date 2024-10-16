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

namespace SP\Domain\Api\Services;

use JsonException;
use SP\Domain\Api\Dtos\ApiRequestData;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__u;

/**
 * Class ApiRequest
 */
final class ApiRequest implements ApiRequestService
{
    protected ?string         $method = null;
    protected ?int            $id     = null;
    protected ?ApiRequestData $data   = null;

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
                JsonRpcResponse::PARSE_ERROR
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
                $e->getMessage(),
                JsonRpcResponse::PARSE_ERROR
            );
        }

        if (!isset(
            $data['jsonrpc'],
            $data['method'],
            $data['id'],
            $data['params']['authToken']
        )
        ) {
            throw new ApiRequestException(
                __u('Invalid format'),
                SPException::ERROR,
                null,
                JsonRpcResponse::INVALID_REQUEST
            );
        }

        $apiRequest = new ApiRequest();
        $apiRequest->method = preg_replace('#[^a-z/]+#i', '', $data['method']);
        $apiRequest->id = filter_var($data['id'], FILTER_VALIDATE_INT) ?: 1;
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
