<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Util;
use SP\Core\SPException;


/**
 * Class Json con utilidades para JSON
 *
 * @package SP\Util
 */
class Json
{
    /**
     * Devuelve una cadena en formato JSON
     *
     * @param $data
     * @return string
     * @throws SPException
     */
    public static function getJson($data)
    {
        $json = json_encode(self::safeJson($data));

        if ($json === false) {
            throw new SPException(SPException::SP_CRITICAL, sprintf('%s : %s', _('Error de codificación'), json_last_error_msg()));
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
                            self::safeJsonString($attribute);
                        }

                        return $value;
                    } else {
                        return self::safeJsonString($value);
                    }
                }
            );
        } elseif (is_string($data)) {
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
        $strFrom = array("\\", '"', "'");
        $strTo = array("\\", '\"', "\'");

        return str_replace($strFrom, $strTo, $string);
    }
}