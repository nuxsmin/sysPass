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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase con utilizades para la aplicación
 */
class Util
{
    /**
     * Comprobar si la función de números aleatorios está disponible.
     *
     * @return bool
     */
    public static function secureRNG_available()
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
    public static function runningOnWindows()
    {
        return (substr(PHP_OS, 0, 3) === "WIN");
    }

    /**
     * Generar una cadena aleatoria usuando criptografía.
     *
     * @param int $length opcional, con la longitud de la cadena
     * @return string
     */
    public static function generate_random_bytes($length = 30)
    {
        // Try to use openssl_random_pseudo_bytes
        if (function_exists('openssl_random_pseudo_bytes')) {
            $pseudo_byte = bin2hex(openssl_random_pseudo_bytes($length, $strong));
            if ($strong == true) {
                return substr($pseudo_byte, 0, $length); // Truncate it to match the length
            }
        }

        // Try to use /dev/urandom
        $fp = @file_get_contents('/dev/urandom', false, null, 0, $length);
        if ($fp !== false) {
            $string = substr(bin2hex($fp), 0, $length);
            return $string;
        }

        // Fallback to mt_rand()
        $characters = '0123456789';
        $characters .= 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters) - 1;
        $pseudo_byte = "";

        // Select some random characters
        for ($i = 0; $i < $length; $i++) {
            $pseudo_byte .= $characters[mt_rand(0, $charactersLength)];
        }

        return $pseudo_byte;
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
     * Devuelve el valor de la variable enviada por un formulario.
     *
     * @param string $s con el nombre de la variable
     * @param string $d con el valor por defecto
     * @return string con el valor de la variable
     */
    public static function init_var($s, $d = "")
    {
        $r = $d;
        if (isset($_REQUEST[$s]) && !empty($_REQUEST[$s])) {
            $r = Html::sanitize($_REQUEST[$s]);
        }

        return $r;
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
     * Devuelve la versión de sysPass.
     *
     * @return string con la versión
     */
    public static function getVersionString()
    {
        return '1.2-dev';
    }

    /**
     * Comprobar si hay actualizaciones de sysPass disponibles desde internet (github.com)
     * Esta función hace una petición a GitHub y parsea el JSON devuelto para verificar
     * si la aplicación está actualizada
     *
     * @return array|bool
     */
    public static function checkUpdates()
    {
        if (!Config::getValue('checkupdates')) {
            return false;
        }

        $data = self::getDataFromUrl(self::getAppInfo('appupdates'));

        if ($data) {
            $updateInfo = json_decode($data);

            // $updateInfo[0]->tag_name
            // $updateInfo[0]->name
            // $updateInfo[0]->body
            // $updateInfo[0]->tarball_url
            // $updateInfo[0]->zipball_url
            // $updateInfo[0]->published_at
            // $updateInfo[0]->html_url

            $version = $updateInfo->tag_name;
            $url = $updateInfo->html_url;
            $title = $updateInfo->name;
            $description = $updateInfo->body;
            $date = $updateInfo->published_at;

            preg_match('/v?(\d+)\.(\d+)\.(\d+)\.(\d+)(\-[a-z0-9.]+)?$/', $version, $realVer);

            if (is_array($realVer) && Init::isLoggedIn()) {
                $appVersion = implode('', self::getVersion(true));
                $pubVersion = $realVer[1] . $realVer[2] . $realVer[3] . $realVer[4];

                if ($pubVersion > $appVersion) {
                    return array(
                        'version' => $version,
                        'url' => $url,
                        'title' => $title,
                        'description' => $description,
                        'date' => $date);
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Comprobar si hay notificaciones de sysPass disponibles desde internet (github.com)
     * Esta función hace una petición a GitHub y parsea el JSON devuelto
     *
     * @return array|bool
     */
    public static function checkNotices()
    {
        if (!Config::getValue('checknotices')) {
            return false;
        }

        $data = self::getDataFromUrl(self::getAppInfo('appnotices'));

        if ($data) {
            $noticesData = json_decode($data);
            $notices = array();

            // $noticesData[0]->title
            // $noticesData[0]->body
            // $noticesData[0]->created_at

            foreach ($noticesData as $notice) {
                $notices[] = array(
                    $notice->title,
//                    $notice->body,
                    $notice->created_at
                );
            }

            return $notices;
        }

        return false;
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
     * Devuelve la versión de sysPass.
     *
     * @param bool $retBuild devolver el número de compilación
     * @return array con el número de versión
     */
    public static function getVersion($retBuild = false)
    {
        $build = '01';
        $version = array(1, 2, 1);

        if ($retBuild) {
            array_push($version, $build);
        }

        return $version;
    }


    /**
     * Realiza el proceso de logout.
     */
    public static function logout()
    {
        exit('<script>sysPassUtil.Common.doLogout();</script>');
    }

    /**
     * Obtener el tamaño máximo de subida de PHP.
     */
    public static function getMaxUpload()
    {
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        Log::writeNewLog(__FUNCTION__, "Max. PHP upload: " . $upload_mb . "MB");
    }

    /**
     * Comprobar si está en modo DEMO.
     *
     * @return bool
     */
    public static function demoIsEnabled()
    {
        return self::boolval(Config::getValue('demo_enabled', false));
    }

    /**
     * Checks a variable to see if it should be considered a boolean true or false.
     * Also takes into account some text-based representations of true of false,
     * such as 'false','N','yes','on','off', etc.
     *
     * @author Samuel Levy <sam+nospam@samuellevy.com>
     * @param mixed $in     The variable to check
     * @param bool  $strict If set to false, consider everything that is not false to
     *                      be true.
     * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
     */
    public static function boolval($in, $strict = false)
    {
        $in = (is_string($in) ? strtolower($in) : $in);

        // if not strict, we only have to check if something is false
        if (in_array($in, array('false', 'no', 'n', '0', 'off', false, 0), true) || !$in) {
            return false;
        } else if ($strict) {
            // if strict, check the equivalent true values
            if (in_array($in, array('true', 'yes', 'y', '1', 'on', true, 1), true)) {
                return true;
            }
        } else {
            // not strict? let the regular php bool check figure it out (will
            // largely default to true)
            return ($in ? true : false);
        }
    }

    /**
     * Comprobar si está habilitada la gestión de archivos.
     *
     * @return bool
     */
    public static function fileIsEnabled()
    {
        return self::boolval(Config::getValue('files_enabled', false));
    }

    /**
     * Comprobar si están habilitadas las notificaciones por correo.
     *
     * @return bool
     */
    public static function mailIsEnabled()
    {
        return self::boolval(Config::getValue('mail_enabled', false));
    }

    /**
     * Comprobar si está habilitada la Wiki.
     *
     * @return bool
     */
    public static function wikiIsEnabled()
    {
        return self::boolval(Config::getValue('wiki_enabled', false));
    }

    /**
     * Comprobar si están habilitadas las peticiones por correo.
     *
     * @return bool
     */
    public static function mailrequestIsEnabled()
    {
        return self::boolval(Config::getValue('mail_requestsenabled', false));
    }

    /**
     * Comprobar si está habilitado LDAP.
     *
     * @return bool
     */
    public static function ldapIsEnabled()
    {
        return self::boolval(Config::getValue('ldap_enabled', false));
    }

    /**
     * Comprobar si está habilitado el log de eventos.
     *
     * @return bool
     */
    public static function logIsEnabled()
    {
        return self::boolval(Config::getValue('log_enabled', false));
    }

    /**
     * Comprobar si está habilitado el formato de tarjeta en los resultados.
     *
     * @return bool
     */
    public static function resultsCardsIsEnabled()
    {
        return self::boolval(Config::getValue('resultsascards', false));
    }

    /**
     * Comprobar si está habilitado usar imagen para claves de cuentas
     *
     * @return bool
     */
    public static function accountPassToImageIsEnabled()
    {
        return self::boolval(Config::getValue('account_passtoimage', false));
    }

    /**
     * Comprobar si está habilitado forzar la conexión por HTTPS
     *
     * @return bool
     */
    public static function forceHttpsIsEnabled()
    {
        return self::boolval(Config::getValue('https_enabled', false));
    }

    /**
     * Comprobar si está habilitado la publicación de enlaces
     *
     * @return bool
     */
    public static function publicLinksIsEnabled()
    {
        return self::boolval(Config::getValue('publinks_enabled', false));
    }

    /**
     * Comprobar si se utiliza HTTPS
     *
     * @return bool
     */
    public static function httpsEnabled() {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * Establecer variable de sesión para recargar la aplicación.
     */
    public static function reload()
    {
        if (Session::getReload() === false) {
            Session::setReload(true);
        }
    }

    /**
     * Comprobar si se necesita recargar la aplicación.
     */
    public static function checkReload()
    {
        if (Session::getReload() === true) {
            Session::setReload(false);
            exit("<script>location.reload();</script>");
        }
    }


    /**
     * Recorrer un array y escapar los carácteres no válidos en Javascript.
     *
     * @param $array
     * @return array
     */
    public static function arrayJSEscape(&$array)
    {
        array_walk($array, function (&$value, $index) {
            $value = str_replace(array("'", '"'), "\\'", $value);
        });
        return $array;
    }

    /**
     * Devuelve información sobre la aplicación.
     *
     * @param string $index con la key a devolver
     * @return array con las propiedades de la aplicación
     */
    public static function getAppInfo($index = null)
    {
        $appinfo = array(
            'appname' => 'sysPass',
            'appdesc' => 'Systems Password Manager',
            'appwebsite' => 'http://www.syspass.org',
            'appblog' => 'http://www.cygnux.org',
            'appdoc' => 'http://wiki.syspass.org',
            'appupdates' => 'https://api.github.com/repos/nuxsmin/sysPass/releases/latest',
            'appnotices' => 'https://api.github.com/repos/nuxsmin/sysPass/issues?milestone=none&state=open&labels=Notices',
            'apphelp' => 'https://github.com/nuxsmin/sysPass/issues',
            'appchangelog' => 'https://github.com/nuxsmin/sysPass/blob/master/CHANGELOG');

        if (!is_null($index) && isset($appinfo[$index])) {
            return $appinfo[$index];
        }

        return $appinfo;
    }

    /**
     * Obtener datos desde una URL usando CURL
     *
     * @param $url string La URL
     * @return bool|string
     */
    public static function getDataFromUrl($url)
    {
        if (!self::curlIsAvailable()) {
            return false;
        }

        $ch = curl_init($url);

        if (Config::getValue('proxy_enabled')){
            curl_setopt($ch, CURLOPT_PROXY, Config::getValue('proxy_server'));
            curl_setopt($ch, CURLOPT_PROXYPORT, Config::getValue('proxy_port'));
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

            $proxyUser = Config::getValue('proxy_user');

            if ($proxyUser) {
                $proxyAuth = $proxyUser . ':' . Config::getValue('proxy_pass');
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "sysPass-App");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $data = curl_exec($ch);

        if ($data === false) {
            Log::writeNewLog(__FUNCTION__, curl_error($ch));

            return false;
        }

        return $data;
    }

    /**
     * Obtener la URL de acceso al servidor
     *
     * @return string
     */
    public static function getServerUrl()
    {
        $urlScheme = (self::httpsEnabled()) ? 'https://' : 'http://';
        $urlPort = ($_SERVER['SERVER_PORT'] != 443) ? ':' . $_SERVER['SERVER_PORT'] : '';

        return $urlScheme . $_SERVER['SERVER_NAME'] . $urlPort;
    }
}