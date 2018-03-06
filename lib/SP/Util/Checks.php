<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use Klein\Klein;

/**
 * Class Checks utilidades de comprobación
 *
 * @package SP\Util
 */
class Checks
{
    /**
     * Comprobar si la función de números aleatorios está disponible.
     *
     * @return bool
     */
    public static function secureRNGIsAvailable()
    {
        // Check openssl_random_pseudo_bytes
        if (function_exists('openssl_random_pseudo_bytes')) {
            openssl_random_pseudo_bytes(1, $strong);

            if ($strong === true) {
                return true;
            }
        }

        // Check /dev/urandom
        $fp = @file_get_contents('/dev/urandom', false, null, 0, 1);

        return $fp !== false;
    }

    /**
     * Comprobar si sysPass se ejecuta en W$indows.
     *
     * @return bool
     */
    public static function checkIsWindows()
    {
        return 0 === strpos(PHP_OS, 'WIN');
    }

    /**
     * Comprobar la versión de PHP.
     *
     * @return bool
     */
    public static function checkPhpVersion()
    {
        return PHP_VERSION_ID >= 50600 && version_compare(PHP_VERSION, '7.1.0') === -1;
    }

    /**
     * Comprobar los módulos necesarios.
     *
     * @return array con los módulos no disponibles
     */
    public static function checkModules()
    {
        $modsNeed = [
            'ldap',
            'mcrypt',
            'curl',
            'SimpleXML',
            'Phar',
            'json',
            'xml',
            'PDO',
            'zlib',
            'gettext',
            'openssl',
            'pcre',
            'session',
            'gd',
            'mbstring'
        ];

        $missing = [];

        foreach ($modsNeed as $module) {
            if (!extension_loaded($module)) {
                $missing[] = $module;
            }
        }

        return $missing;
    }

    /**
     * Comprobar si el módulo de LDAP está instalado.
     *
     * @return bool
     */
    public static function ldapIsAvailable()
    {
        return extension_loaded('ldap');
    }

    /**
     * Comprobar si el módulo CURL está instalado.
     *
     * @return bool
     */
    public static function curlIsAvailable()
    {
        return extension_loaded('curl');
    }

    /**
     * Comprobar si el módulo GD está instalado.
     *
     * @return bool
     */
    public static function gdIsAvailable()
    {
        return extension_loaded('gd');
    }

    /**
     * Comprobar si se utiliza HTTPS
     *
     * @return bool
     */
    public static function httpsEnabled()
    {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int)$_SERVER['SERVER_PORT'] === 443;
    }

    /**
     * Comprobar si la petición es Ajax
     *
     * @param Klein $router
     * @return bool
     */
    public static function isAjax(Klein $router)
    {
        return $router->request()->headers()->get('X-Requested-With') === 'XMLHttpRequest'
            || (int)$router->request()->param('isAjax') === 1;
    }

    /**
     * Comprobar si la petición es en formato JSON
     *
     * @param Klein $router
     * @return bool
     */
    public static function isJson(Klein $router)
    {
        return $router->request()->headers()->get('Accept') === 'application/json';
    }
}
