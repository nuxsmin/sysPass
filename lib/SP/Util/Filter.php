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

namespace SP\Util;

defined('APP_ROOT') || die();

/**
 * Class Filter para el filtrado de datos
 *
 * @package SP\Util
 */
final class Filter
{
    /**
     * Limpiar una cadena de búsqueda de carácteres utilizados en expresiones regulares
     *
     * @param $string
     *
     * @return mixed
     */
    public static function safeSearchString($string)
    {
        return str_replace(['/', '[', '\\', ']', '%', '{', '}', '*', '$'], '', (string)$string);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public static function getEmail($value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public static function getArray(array $array): array
    {
        return array_map(function ($value) {
            if ($value !== null) {
                if (is_numeric($value)) {
                    return Filter::getInt($value);
                } else {
                    return Filter::getString($value);
                }
            }

            return null;
        }, $array);
    }

    /**
     * @param $value
     *
     * @return int
     */
    public static function getInt($value): int
    {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public static function getString($value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public static function getRaw($value): string
    {
        return filter_var(trim($value), FILTER_UNSAFE_RAW);
    }
}