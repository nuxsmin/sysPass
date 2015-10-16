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

namespace SP\Util;

use SP\Config\Config;
use SP\Core\SPException;

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
            if ($strong == true) {
                return true;
            }
        }

        // Check /dev/urandom
        $fp = @file_get_contents('/dev/urandom', false, null, 0, 1);
        if ($fp !== false) {
            return true;
        }

        return false;
    }

    /**
     * Comprobar si sysPass se ejecuta en W$indows.
     *
     * @return bool
     */
    public static function checkIsWindows()
    {
        return (substr(PHP_OS, 0, 3) === "WIN");
    }

    /**
     * Comprobar la versión de PHP.
     *
     * @return bool
     */
    public static function checkPhpVersion()
    {
        $error = array();
        $needsVersion = '5.3.0';

        if (version_compare(PHP_VERSION, $needsVersion, '>=')) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Versión de PHP requerida >= ') . $needsVersion,
                'hint' => _('Actualice la versión de PHP para que la aplicación funcione correctamente')
            );
        }

        return $error;
    }

    /**
     * Comprobar los módulos necesarios.
     *
     * @return array con los módulos no disponibles
     */
    public static function checkModules()
    {
        $modsNeed = array(
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
            'gd'
        );
        $error = array();

        foreach ($modsNeed as $module) {
            if (!extension_loaded($module)) {
                $error[] = array(
                    'type' => SPException::SP_WARNING,
                    'description' => sprintf('%s (%s)', _('Módulo no disponible'), $module),
                    'hint' => _('Sin este módulo la aplicación puede no funcionar correctamente.')
                );
            }
        }

        return $error;
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
        return (function_exists('curl_init'));
    }

    /**
     * Comprobar si está en modo DEMO.
     *
     * @return bool
     */
    public static function demoIsEnabled()
    {
        return Util::boolval(Config::getValue('demo_enabled', false));
    }

    /**
     * Comprobar si está habilitada la gestión de archivos.
     *
     * @return bool
     */
    public static function fileIsEnabled()
    {
        return Util::boolval(Config::getValue('files_enabled', false));
    }

    /**
     * Comprobar si están habilitadas las notificaciones por correo.
     *
     * @return bool
     */
    public static function mailIsEnabled()
    {
        return Util::boolval(Config::getValue('mail_enabled', false));
    }

    /**
     * Comprobar si está habilitada la Wiki.
     *
     * @return bool
     */
    public static function wikiIsEnabled()
    {
        return Util::boolval(Config::getValue('wiki_enabled', false));
    }

    /**
     * Comprobar si están habilitadas las peticiones por correo.
     *
     * @return bool
     */
    public static function mailrequestIsEnabled()
    {
        return Util::boolval(Config::getValue('mail_requestsenabled', false));
    }

    /**
     * Comprobar si está habilitado LDAP.
     *
     * @return bool
     */
    public static function ldapIsEnabled()
    {
        return Util::boolval(Config::getValue('ldap_enabled', false));
    }

    /**
     * Comprobar si está habilitado el log de eventos.
     *
     * @return bool
     */
    public static function logIsEnabled()
    {
        return Util::boolval(Config::getValue('log_enabled', false));
    }

    /**
     * Comprobar si está habilitado el servidor de syslog.
     *
     * @return bool
     */
    public static function syslogIsEnabled()
    {
        return Util::boolval(Config::getValue('syslog_enabled', false));
    }

    /**
     * Comprobar si está habilitado el servidor de syslog.
     *
     * @return bool
     */
    public static function remoteSyslogIsEnabled()
    {
        return Util::boolval(Config::getValue('syslog_remote_enabled', false));
    }


    /**
     * Comprobar si está habilitado el formato de tarjeta en los resultados.
     *
     * @return bool
     */
    public static function resultsCardsIsEnabled()
    {
        return Util::boolval(Config::getValue('resultsascards', false));
    }

    /**
     * Comprobar si está habilitado usar imagen para claves de cuentas
     *
     * @return bool
     */
    public static function accountPassToImageIsEnabled()
    {
        return Util::boolval(Config::getValue('account_passtoimage', false));
    }

    /**
     * Comprobar si está habilitado forzar la conexión por HTTPS
     *
     * @return bool
     */
    public static function forceHttpsIsEnabled()
    {
        return Util::boolval(Config::getValue('https_enabled', false));
    }

    /**
     * Comprobar si está habilitado la publicación de enlaces
     *
     * @return bool
     */
    public static function publicLinksIsEnabled()
    {
        return Util::boolval(Config::getValue('publinks_enabled', false));
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
            || $_SERVER['SERVER_PORT'] == 443;
    }
}