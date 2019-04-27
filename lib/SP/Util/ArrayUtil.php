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

/**
 * Class ArrayUtil
 *
 * @package SP\Util
 */
final class ArrayUtil
{
    /**
     * Buscar un objeto en un array según el valor de una propiedad
     *
     * @param array  $array
     * @param string $property Nombre de la propiedad
     * @param string $value    Valor de la propiedad
     * @param object $default  Valor por defecto
     *
     * @return false|object
     */
    public static function searchInObject(array $array, $property, $value, $default = null)
    {
        foreach ($array as $object) {
            if (is_object($object)
                && isset($object->$property)
                && $value == $object->$property
            ) {
                return $object;
            }
        }

        return null !== $default ? $default : false;
    }

    /**
     * Comprobar si un valor existe en un array de objetos
     *
     * @param array  $objectArray
     * @param string $method
     * @param mixed  $value
     *
     * @return bool
     */
    public static function checkInObjectArrayMethod(array $objectArray, $method, $value)
    {
        foreach ($objectArray as $object) {
            if (is_callable([$object, $method]) && $object->$method() === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprobar si un valor existe en un array de objetos
     *
     * @param array  $objectArray
     * @param string $property
     * @param mixed  $value
     *
     * @return bool
     */
    public static function checkInObjectArray(array $objectArray, $property, $value)
    {
        if (empty($objectArray)) {
            return false;
        }

        foreach ($objectArray as $object) {
            if (is_object($object)
                && isset($object->$property)
                && $object->$property == $value
            ) {
                return true;
            }
        }

        return false;
    }
}