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

namespace SP\Util;

/**
 * Class Filter para el filtrado de datos
 *
 * @package SP\Util
 */
final class Filter
{
    private const UNSAFE_CHARS = ['/', '[', '\\', ']', '%', '{', '}', '*', '$'];

    /**
     * Limpiar una cadena de búsqueda de carácteres utilizados en expresiones regulares
     */
    public static function safeSearchString(string $string): string
    {
        return str_replace(self::UNSAFE_CHARS, '', $string);
    }

    public static function getEmail(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    public static function getArray(array $array): array
    {
        return array_map(
            static function ($value) {
                if ($value !== null) {
                    return is_numeric($value)
                        ? Filter::getInt($value)
                        : Filter::getString($value);
                }

                return null;
            },
            $array
        );
    }

    /**
     * @param  int|string  $value
     *
     * @return int|null
     */
    public static function getInt(int|string $value): ?int
    {
        $filterVar = filter_var($value, FILTER_SANITIZE_NUMBER_INT);

        return is_numeric($filterVar) ? (int)$filterVar : null;
    }

    public static function getString(?string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    public static function getRaw(string $value): string
    {
        return filter_var(trim($value), FILTER_UNSAFE_RAW);
    }
}
