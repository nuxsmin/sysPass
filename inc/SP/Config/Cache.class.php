<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

namespace SP\Config;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase base para guardar/obtener elementos de la caché
 */
class Cache
{
    /**
     * Tiempo de expiración de la cache en segundos
     */
    const EXPIRE_TIME = 300;

    /**
     * Obtener un parámetro de la configuración de sysPass desde la caché de la sesión
     *
     * @param string $param El parámetro a obtener
     * @return null
     */
    public static function getSessionCacheConfigValue($param)
    {
        $config = self::getSessionCacheConfig();

        if (isset($config) && isset($config[$param])) {
            return $config[$param];
        }

        return null;
    }

    /**
     * Obtener la configuración de sysPass desde la caché de la sesión
     *
     * @return array|bool Los datos de la configuración
     */
    public static function getSessionCacheConfig()
    {
        if (isset($_SESSION['cache']['config']) && is_array($_SESSION['cache']['config'])) {
            $isExpired = (time() - $_SESSION['cache']['config']['expires'] > 0);

            if (!$isExpired) {
                return $_SESSION['cache']['config'];
            }
        }

        self::setSessionCacheConfig();

        return $_SESSION['cache']['config'];
    }

    /**
     * Guardar la cache de configuración en la sesion
     */
    public static function setSessionCacheConfig()
    {
        $_SESSION['cache']['config'] = Config::getConfig();
        $_SESSION['cache']['config']['expires'] = time() + self::EXPIRE_TIME;
    }
}