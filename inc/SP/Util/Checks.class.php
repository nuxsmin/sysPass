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

use SP\Config\Config;
use SP\Http\Request;

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
        return version_compare(PHP_VERSION, '5.6.0', '>=') && version_compare(PHP_VERSION, '7.1.0') === -1;
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
     * Comprobar si está en modo DEMO.
     *
     * @return bool
     */
    public static function demoIsEnabled()
    {
        return Config::getConfig()->isDemoEnabled();
    }

    /**
     * Comprobar si está habilitada la gestión de archivos.
     *
     * @return bool
     */
    public static function fileIsEnabled()
    {
        return Config::getConfig()->isFilesEnabled();
    }

    /**
     * Comprobar si están habilitadas las notificaciones por correo.
     *
     * @return bool
     */
    public static function mailIsEnabled()
    {
        return Config::getConfig()->isMailEnabled();
    }

    /**
     * Comprobar si está habilitada la Wiki.
     *
     * @return bool
     */
    public static function wikiIsEnabled()
    {
        return Config::getConfig()->isWikiEnabled();
    }

    /**
     * Comprobar si está habilitada la API de DokuWiki.
     *
     * @return bool
     */
    public static function dokuWikiIsEnabled()
    {
        return Config::getConfig()->isDokuwikiEnabled();
    }

    /**
     * Comprobar si están habilitadas las peticiones por correo.
     *
     * @return bool
     */
    public static function mailrequestIsEnabled()
    {
        return Config::getConfig()->isMailRequestsEnabled();
    }

    /**
     * Comprobar si está habilitado LDAP.
     *
     * @return bool
     */
    public static function ldapIsEnabled()
    {
        return Config::getConfig()->isLdapEnabled();
    }

    /**
     * Comprobar si está habilitado el log de eventos.
     *
     * @return bool
     */
    public static function logIsEnabled()
    {
        return Config::getConfig()->isLogEnabled();
    }

    /**
     * Comprobar si está habilitado el servidor de syslog.
     *
     * @return bool
     */
    public static function syslogIsEnabled()
    {
        return Config::getConfig()->isSyslogEnabled();
    }

    /**
     * Comprobar si está habilitado el servidor de syslog.
     *
     * @return bool
     */
    public static function remoteSyslogIsEnabled()
    {
        return Config::getConfig()->isSyslogRemoteEnabled();
    }


    /**
     * Comprobar si está habilitado el formato de tarjeta en los resultados.
     *
     * @return bool
     */
    public static function resultsCardsIsEnabled()
    {
        return Config::getConfig()->isResultsAsCards();
    }

    /**
     * Comprobar si está habilitado usar imagen para claves de cuentas
     *
     * @return bool
     */
    public static function accountPassToImageIsEnabled()
    {
        return Config::getConfig()->isAccountPassToImage();
    }

    /**
     * Comprobar si está habilitado forzar la conexión por HTTPS
     *
     * @return bool
     */
    public static function forceHttpsIsEnabled()
    {
        return Config::getConfig()->isHttpsEnabled();
    }

    /**
     * Comprobar si está habilitado la publicación de enlaces
     *
     * @return bool
     */
    public static function publicLinksIsEnabled()
    {
        return Config::getConfig()->isPublinksEnabled();
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
            || $_SERVER['SERVER_PORT'] === 443;
    }

    /**
     * Comprobar si la petición es Ajax
     *
     * @return bool
     */
    public static function isAjax()
    {
        return Request::getRequestHeaders('X-Requested-With') === 'XMLHttpRequest'
            || Request::analyze('isAjax', false, true);
    }

    /**
     * Comprobar si la petición es en formato JSON
     *
     * @return bool
     */
    public static function isJson()
    {
        return strpos(Request::getRequestHeaders('Accept'), 'application/json') === 0;
    }
}