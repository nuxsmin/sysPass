<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Http;

use JsonException;
use Klein\Klein;
use Klein\Response;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Exceptions\SPException;


/**
 * Class Json con utilidades para JSON
 *
 * @package SP\Util
 */
final class Json
{
    public const SAFE = [
        'from' => ['\\', '"', '\''],
        'to'   => ['\\', '\"', '\\\''],
    ];

    private Response $response;

    /**
     * Json constructor.
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public static function factory(Response $response): Json
    {
        return new self($response);
    }

    /**
     * @return Json
     */
    public static function fromDic(): Json
    {
        return new self(
            BootstrapBase::getContainer()
                ->get(Klein::class)
                ->response()
        );
    }

    /**
     * Devuelve un array con las cadenas formateadas para JSON
     */
    public static function safeJson(&$data): string
    {
        if (is_array($data) || is_object($data)) {
            array_walk_recursive(
                $data,
                static function (&$value) {
                    if (is_object($value)) {
                        foreach ($value as $property => $v) {
                            if (is_string($v) && $v !== '') {
                                $value->$property = self::safeJsonString($v);
                            }
                        }

                        return $value;
                    }

                    if (is_string($value) && $value !== '') {
                        return self::safeJsonString($value);
                    }

                    return $value;
                }
            );
        } elseif (is_string($data) && $data !== '') {
            return self::safeJsonString($data);
        }

        return $data;
    }

    /**
     * Devuelve una cadena con los carácteres formateadas para JSON
     *
     * @return array|string|string[]
     */
    public static function safeJsonString($string)
    {
        return str_replace(self::SAFE['from'], self::SAFE['to'], $string);
    }

    /**
     * Devuelve una respuesta en formato JSON
     *
     * @param  string  $data  JSON string
     *
     * @return bool
     */
    public function returnRawJson(string $data): bool
    {
        return $this->response
            ->header('Content-type', 'application/json; charset=utf-8')
            ->body($data)
            ->send(true)
            ->isSent();
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * @throws \JsonException
     */
    public function returnJson(JsonResponse $jsonResponse): bool
    {
        $this->response->header('Content-type', 'application/json; charset=utf-8');

        try {
            $this->response->body(self::getJson($jsonResponse));
        } catch (SPException $e) {
            $jsonResponse = new JsonResponse($e->getMessage());
            $jsonResponse->addMessage($e->getHint());

            $this->response->body(json_encode($jsonResponse, JSON_THROW_ON_ERROR));
        }

        return $this->response->send(true)->isSent();
    }

    /**
     * Devuelve una cadena en formato JSON
     *
     * @param  mixed  $data
     * @param  int  $flags  JSON_* flags
     *
     * @throws SPException
     */
    public static function getJson($data, int $flags = 0): string
    {

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | $flags);
        } catch (JsonException $e) {
            throw new SPException(
                __u('Encoding error'),
                SPException::CRITICAL,
                json_last_error_msg()
            );
        }

        return $json;
    }
}