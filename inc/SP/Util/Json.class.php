<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Util;

use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;


/**
 * Class Json con utilidades para JSON
 *
 * @package SP\Util
 */
class Json
{
    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * @param JsonResponse $JsonResponse
     * @return bool
     */
    public static function returnJson(JsonResponse $JsonResponse)
    {
        header('Content-type: application/json; charset=utf-8');

        try {
            exit(self::getJson($JsonResponse));
        } catch (SPException $e) {
            $JsonResponse = new JsonResponse();
            $JsonResponse->setDescription($e->getMessage());
            $JsonResponse->addMessage($e->getHint());

            exit(json_encode($JsonResponse));
        }
    }

    /**
     * Devuelve una cadena en formato JSON
     *
     * @param $data
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function getJson($data)
    {
        $json = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($json === false) {
            throw new SPException(SPException::SP_CRITICAL, __('Error de codificación', false), json_last_error_msg());
        }

        return $json;
    }

    /**
     * Devuelve un array con las cadenas formateadas para JSON
     *
     * @param $data mixed
     * @return mixed
     */
    public static function safeJson(&$data)
    {
        if (is_array($data) || is_object($data)) {
            array_walk_recursive($data,
                function (&$value) {
                    if (is_object($value)) {
                        foreach ($value as &$attribute) {
                            if (is_string($attribute) && $attribute !== '') {
                                self::safeJsonString($attribute);
                            }
                        }

                        return $value;
                    } elseif (is_string($value) && $value !== '') {
                        return self::safeJsonString($value);
                    } else {
                        return $value;
                    }
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
     * @return mixed
     */
    public static function safeJsonString(&$string)
    {
        $strFrom = ['\\', '"', '\''];
        $strTo = ['\\', '\"', '\\\''];

        $string = str_replace($strFrom, $strTo, $string);

        return $string;
    }
}