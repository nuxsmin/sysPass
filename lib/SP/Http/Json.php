<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Http;

use Klein\Klein;
use Klein\Response;
use SP\Bootstrap;
use SP\Core\Exceptions\SPException;


/**
 * Class Json con utilidades para JSON
 *
 * @package SP\Util
 */
final class Json
{
    const SAFE = [
        'from' => ['\\', '"', '\''],
        'to' => ['\\', '\"', '\\\'']
    ];

    /**
     * @var Response
     */
    private $response;

    /**
     * Json constructor.
     *
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @param Response $response
     *
     * @return Json
     */
    public static function factory(Response $response)
    {
        return new self($response);
    }

    /**
     * @return Json
     */
    public static function fromDic()
    {
        return new self(Bootstrap::getContainer()->get(Klein::class)->response());
    }

    /**
     * Devuelve un array con las cadenas formateadas para JSON
     *
     * @param $data mixed
     *
     * @return mixed
     */
    public static function safeJson(&$data)
    {
        if (is_array($data) || is_object($data)) {
            array_walk_recursive($data,
                function (&$value) {
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
     * @param $string
     *
     * @return mixed
     */
    public static function safeJsonString($string)
    {
        return str_replace(self::SAFE['from'], self::SAFE['to'], $string);
    }

    /**
     * Devuelve una respuesta en formato JSON
     *
     * @param string $data JSON string
     *
     * @return bool
     */
    public function returnRawJson(string $data)
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
     * @param JsonResponse $jsonResponse
     *
     * @return bool
     */
    public function returnJson(JsonResponse $jsonResponse)
    {
        $this->response->header('Content-type', 'application/json; charset=utf-8');

        try {
            $this->response->body(self::getJson($jsonResponse));
        } catch (SPException $e) {
            $jsonResponse = new JsonResponse($e->getMessage());
            $jsonResponse->addMessage($e->getHint());

            $this->response->body(json_encode($jsonResponse));
        }

        return $this->response->send(true)->isSent();
    }

    /**
     * Devuelve una cadena en formato JSON
     *
     * @param mixed $data
     * @param int   $flags JSON_* flags
     *
     * @return string La cadena en formato JSON
     * @throws SPException
     */
    public static function getJson($data, $flags = 0)
    {
        $json = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | $flags);

        if ($json === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new SPException(
                __u('Encoding error'),
                SPException::CRITICAL,
                json_last_error_msg()
            );
        }

        return $json;
    }
}