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

use CssMin;

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
//        $modsAvail = array_map('strtolower', get_loaded_extensions());
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
            'session'
        );
        $error = array();

        foreach ($modsNeed as $module) {
            if (!extension_loaded($module)) {
                $error[] = array(
                    'type' => SPException::SP_WARNING,
                    'description' => _('Módulo no disponible') . " ($module)",
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
        return '1.2-beta';
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
        if (!self::curlIsAvailable() || !Config::getValue('checkupdates')) {
            return false;
        }

        $githubUrl = 'https://api.github.com/repos/nuxsmin/sysPass/releases/latest';
        $ch = curl_init($githubUrl);

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
        curl_setopt($ch, CURLOPT_USERAGENT, "sysPass App Updater");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $data = curl_exec($ch);

        if ($data === false) {
            Log::writeNewLog(__FUNCTION__, curl_error($ch));

            return false;
        }

        curl_close($ch);

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

        preg_match("/v?(\d+)\.(\d+)\.(\d+)\.(\d+)(\-[a-z0-9.]+)?$/", $version, $realVer);

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
        $version = array(1, 2, 0);

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
        exit('<script>doLogout();</script>');
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
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     *
     * @param string $type  tipo de recurso a devolver
     * @param array  $files archivos a parsear
     * @param bool   $disableMinify Deshabilitar minimizar
     */
    public static function getMinified($type, &$files, $disableMinify = false)
    {
        $offset = 3600 * 24 * 30;
        $nextCheck = time() + $offset;
        $expire = 'Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $nextCheck);
        //$etag = md5(implode(SP_Util::getVersion()));
        $etag = self::getEtag($files);
        $etagMatch = self::getRequestHeaders('If-None-Match');
        $cacheControl = self::getRequestHeaders('Cache-Control');
        $pragma = self::getRequestHeaders('Pragma');

        header('Etag: ' . $etag);
        header("Cache-Control: public, max-age={$offset}, must-revalidate");
        header("Pragma: public; maxage={$offset}");
        header($expire);

        // Devolver código 304 si la versión es la misma y no se solicita refrescar
        if ($etag == $etagMatch && !($cacheControl == 'no-cache' || $pragma == 'no-cache')) {
            header($_SERVER["SERVER_PROTOCOL"] . " 304 Not Modified");
            exit;
        }

        $path = Init::$SERVERROOT . DIRECTORY_SEPARATOR;

        if ($type == 'js') {
            header("Content-type: application/x-javascript; charset: UTF-8");
        } elseif ($type == 'css') {
            header("Content-type: text/css; charset: UTF-8");
        }

        flush();

        if (self::checkZlib() || !ob_start('ob_gzhandler')) {
            ob_start();
        }

        foreach ($files as $file) {
            $filePath = $path . $file['href'];

            // Obtener el recurso desde una URL
            if (preg_match('#^https?://.*#', $file['href'])){
                $data = self::getDataFromUrl($file['href']);

                if($data !== false){
                    echo '/* URL: ' . $file['href'] . ' */' . PHP_EOL;
                    echo $data;
                }

                continue;
            }

            if(!file_exists($filePath)){
                error_log('File not found: ' . $filePath);
                continue;
            }

            if ($file['min'] === true && $disableMinify === false) {
                echo '/* MINIFIED FILE: ' . $file['href'] . ' */' . PHP_EOL;
                if ($type == 'js') {
                    echo self::jsCompress(file_get_contents($filePath));
                } elseif ($type == 'css') {
                    require_once EXTENSIONS_PATH . DIRECTORY_SEPARATOR . 'cssmin' . DIRECTORY_SEPARATOR . 'class.cssmin.php';
                    echo CssMin::minify(file_get_contents($filePath));
                }
            } else {
                echo '/* FILE: ' . $file['href'] . ' */' . PHP_EOL;
                echo file_get_contents($filePath);
            }

            echo PHP_EOL;
        }

        ob_end_flush();
    }

    /**
     * Calcular el hash MD5 de varios archivos.
     *
     * @param array $files archivos a calcular
     * @return string Con el hash
     */
    private static function getEtag(&$files)
    {
        $md5Sum = '';
        $path = Init::$SERVERROOT . DIRECTORY_SEPARATOR;

        foreach ($files as $file) {
            if(preg_match('#^https?://.*#', $file['href'])){
                continue;
            }
            $md5Sum .= md5_file($path . $file['href']);
        }

        return md5($md5Sum);
    }

    /**
     * Devolver las cabeceras enviadas desde el cliente.
     *
     * @param string $header nombre de la cabecera a devolver
     * @return array
     */
    public static function getRequestHeaders($header = '')
    {
        if (!function_exists('apache_request_headers')) {
            function apache_request_headers()
            {
                foreach ($_SERVER as $key => $value) {
                    if (substr($key, 0, 5) == "HTTP_") {
                        $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                        $headers[$key] = $value;
                    } else {
                        $headers[$key] = $value;
                    }
                }
            }
        } else {
            $headers = apache_request_headers();
        }

        if (!empty($header) && array_key_exists($header, $headers)) {
            return $headers[$header];
        } elseif (!empty($header)) {
            return false;
        }

        return $headers;
    }

    /**
     * Comprobar si la salida comprimida en con zlib está activada.
     * No es compatible con ob_gzhandler()
     *
     * @return bool
     */
    public static function checkZlib()
    {
        return self::boolval(ini_get('zlib.output_compression'));
    }

    /**
     * Comprimir código javascript.
     *
     * @param string $buffer código a comprimir
     * @return string
     */
    private static function jsCompress($buffer)
    {
        $regexReplace = array(
            '#/\*[^*]*\*+([^/][^*]*\*+)*/#',
            '#^[\s\t]*//.*$#m',
            '#[\s\t]+$#m',
            '#^[\s\t]+#m',
            '#\s*//\s.*$#m'
        );
        $buffer = preg_replace($regexReplace, '', $buffer);
        // remove tabs, spaces, newlines, etc.
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t"), '', $buffer);
        return $buffer;
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
            'appdesc' => 'Sysadmin Password Manager',
            'appwebsite' => 'http://www.syspass.org',
            'appblog' => 'http://www.cygnux.org',
            'appdoc' => 'http://wiki.syspass.org',
            'appupdates' => 'http://sourceforge.net/api/file/index/project-id/775555/mtime/desc/limit/20/rss',
            'apphelp' => 'help.syspass.org',
            'appchangelog' => '');

        if (!is_null($index) && isset($appinfo[$index])) {
            return $appinfo[$index];
        }

        return $appinfo;
    }

    private static function getDataFromUrl($url)
    {
        $ch = curl_init($url);

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
}