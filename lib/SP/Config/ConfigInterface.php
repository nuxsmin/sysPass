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

namespace SP\Config;

defined('APP_ROOT') || die();

/**
 * Interface ConfigInterface para las clases que gestionan la configuración de sysPass
 *
 * @package SP
 */
interface ConfigInterface
{
    /**
     * Obtiene un valor de configuración
     *
     * @param string $param   El valor a obtener
     * @param string $default El valor por defecto
     *
     * @return string el valor o $default
     */
    public static function getValue($param, $default = null);

    /**
     * Guardar un parámetro de configuración
     *
     * @param string $param     El parámetro a guardar
     * @param string $value     El valor a guardar
     * @param bool   $email     enviar email?
     * @param bool   $hideValue Ocultar el valor del registro en el log
     *
     * @return bool
     */
    public static function setValue($param, $value, $email = true, $hideValue = false);

    /**
     * Elimina un parámetro de la configuración.
     *
     * @param string $param clave
     *
     * @return bool
     */
    public static function deleteParam($param);

    /**
     * Actualizar el array de parámetros de configuración
     *
     * @param $param   string El parámetro a actualizar
     * @param $value   mixed El valor a actualizar
     */
    public static function setCacheConfigValue($param, $value);

    /**
     * Obtener un parámetro del array de parámetros de configuración
     *
     * @param $param   string El parámetro a devolver
     */
    public static function getCacheConfigValue($param);

    /**
     * Obtener un array con la configuración almacenada.
     *
     * @return bool
     */
    public static function readConfig();
}