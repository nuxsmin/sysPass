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

use JsonException;
use SP\Core\Exceptions\SPException;

/**
 * Class ApiRequest
 *
 * @package SP\Domain\Api\Services
 */
final class ApiRequest
{
    protected ?string         $method = null;
    protected ?int            $id     = null;
    protected ?ApiRequestData $data   = null;

    /**
     * ApiRequest constructor.
     *
     * @throws \SP\Domain\Api\Services\ApiRequestException
     */
    public function __construct(?string $request = null)
    {
        if ($request === null) {
            $this->requestFromJsonData($this->getDataFromRequest());
        } else {
            $this->requestFromJsonData($request);
        }
    }

    /**
     * Obtener los datos de la petición
     *
     * Comprueba que el JSON esté bien formado
     *
     * @param  string  $request
     *
     * @return ApiRequest
     * @throws \SP\Domain\Api\Services\ApiRequestException
     */
    public function requestFromJsonData(string $request): ApiRequest
    {
        try {
            $data = json_decode(
                $request,
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

        $this->method = preg_replace(
            '#[^a-z/]+#i',
            '',
            $data['method']
        );
        $this->id = filter_var($data['id'], FILTER_VALIDATE_INT) ?: 1;
        $this->data = new ApiRequestData();
        $this->data->replace($data['params']);

        return $this;
    }

    /**
     * @return string
     * @throws ApiRequestException
     */
    public function getDataFromRequest(): string
    {
        $content = file_get_contents('php://input');

        if (empty($content)) {
            throw new ApiRequestException(
                __u('Invalid data'),
                SPException::ERROR,
                null,
                JsonRpcResponse::PARSE_ERROR
            );
        }

        return $content;
    }

    /**
     * @param  string  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data->get($key, $default);
    }

    /**
     * @param  string  $key
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