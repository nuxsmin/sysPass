<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase con utilizades para la aplicación
 */
class SP_Util
{
    /**
     * @brief Comprobar si la función de números aleatorios está disponible
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
     * @brief Comprobar si sysPass se ejecuta en W$indows
     * @return bool
     */
    public static function runningOnWindows()
    {
        return (substr(PHP_OS, 0, 3) === "WIN");
    }

    /**
     * @brief Generar una cadena aleatoria usuando criptografía
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
     * @brief Comprobar la versión de PHP
     * @return bool
     */
    public static function checkPhpVersion()
    {
        $error = array();

        $version = explode('.', PHP_VERSION);
        $versionId = ($version[0] * 10000 + $version[1] * 100 + $version[2]);

        if ($versionId < 50100) {
            $error[] = array('type' => 'critical',
                'description' => _('Versión de PHP requerida >= 5.1'),
                'hint' => _('Actualice la versión de PHP para que la aplicación funcione correctamente'));
        }

        return $error;
    }

    /**
     * @brief Comprobar los módulos necesarios
     * @return array con los módulos no disponibles
     */
    public static function checkModules()
    {
        $modsAvail = array_map('strtolower', get_loaded_extensions());
        $modsNeed = array("mysqli", "ldap", "mcrypt", "curl", "simplexml", "phar", "json", "xml");
        $modsErr = array();

        foreach ($modsNeed as $module) {
            if (!in_array($module, $modsAvail)) {
                $error = array(
                    'type' => 'warning',
                    'description' => _('Módulo no disponible') . " ($module)",
                    'hint' => _('Sin este módulo la aplicación puede no funcionar correctamente.')
                );
                $modsErr[] = $error;
            }
        }

        return $modsErr;
    }

    /**
     * @brief Devuelve el valor de la variable enviada por un formulario
     * @param string $s con el nombre de la variable
     * @param string $d con el valor por defecto
     * @return string con el valor de la variable
     */
    public static function init_var($s, $d = "")
    {
        $r = $d;
        if (isset($_REQUEST[$s]) && !empty($_REQUEST[$s])) {
            $r = SP_Html::sanitize($_REQUEST[$s]);
        }

        return $r;
    }

    /**
     * @brief Comprobar si el módulo de LDAP está instalado
     * @return bool
     */
    public static function ldapIsAvailable()
    {
        return in_array("ldap", get_loaded_extensions());
    }

    /**
     * @brief Devuelve la versión de sysPass
     * @return string con la versión
     */
    public static function getVersionString()
    {
        return '1.1-rc1';
    }

    /**
     * @brief Comprobar si hay actualizaciones de sysPass disponibles desde internet (github.com)
     * @return array|bool
     *
     * Esta función hace una petición a GitHub y parsea el JSON devuelto para verificar si la aplicación está actualizada
     */
    public static function checkUpdates()
    {
        if (!self::curlIsAvailable() || !SP_Config::getValue('checkupdates')) {
            return false;
        }

        $githubUrl = 'https://api.github.com/repos/nuxsmin/sysPass/releases';
        $ch = curl_init($githubUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "sysPass App Updater");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $data = curl_exec($ch);

        if ($data === false) {
            $message['action'] = __FUNCTION__;
            $message['text'][] = curl_error($ch);

            SP_Log::wrLogInfo($message);

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

        $version = $updateInfo[0]->tag_name;
        $url = $updateInfo[0]->html_url;
        $title = $updateInfo[0]->name;
        $description = $updateInfo[0]->body;
        $date = $updateInfo[0]->published_at;

        preg_match("/v?(\d+)\.(\d+)\.(\d+)\.(\d+)(\-[a-z0-9.]+)?$/", $version, $realVer);

        if (is_array($realVer) && SP_Init::isLoggedIn()) {
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
     * @brief Comprobar si el módulo CURL está instalado
     * @return bool
     */
    public static function curlIsAvailable()
    {
        return (function_exists('curl_init'));
    }

    /**
     * @brief Devuelve la versión de sysPass
     * @param bool $retBuild devolver el número de compilación
     * @return array con el número de versión
     */
    public static function getVersion($retBuild = false)
    {
        $build = 14;
        $version = array(1, 1, 2);

        if ($retBuild) {
            array_push($version, $build);
        }

        return $version;
    }

    /**
     * @brief Comprobar el método utilizado para enviar un formulario
     * @param string $method con el método utilizado.
     * @return none
     */
    public static function checkReferer($method)
    {
        if ($_SERVER['REQUEST_METHOD'] !== $method
            || !isset($_SERVER['HTTP_REFERER'])
            || !preg_match('#' . SP_Init::$WEBROOT . '/.*$#', $_SERVER['HTTP_REFERER'])
        ) {
            SP_Init::initError(_('No es posible acceder directamente a este archivo'));
            exit();
        }
    }

    /**
     * @brief Realiza el proceso de logout
     * @return none
     */
    public static function logout()
    {
        exit('<script>doLogout();</script>');
    }

    /**
     * @brief Obtener el tamaño máximo de subida de PHP
     * @return none
     */
    public static function getMaxUpload()
    {
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        $message['action'] = __FUNCTION__;
        $message['text'][] = "Max. PHP upload: " . $upload_mb . "MB";

        SP_Log::wrLogInfo($message);
    }

    /**
     * @brief Comprobar si está en modo DEMO
     * @return bool
     */
    public static function demoIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'demo_enabled', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['demo_enabled'] = SP_Config::getValue('demo_enabled', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Comprobar si está habilitada la gestión de archivos
     * @return bool
     */
    public static function fileIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'files_enabled', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['files_enabled'] = SP_Config::getValue('files_enabled', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Comprobar si están habilitadas las notificaciones por correo
     * @return bool
     */
    public static function mailIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'mail_enabled', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['mail_enabled'] = SP_Config::getValue('mail_enabled', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Comprobar si está habilitada la Wiki
     * @return bool
     */
    public static function wikiIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'wiki_enabled', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['wiki_enabled'] = SP_Config::getValue('wiki_enabled', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Comprobar si están habilitadas las peticiones por correo
     * @return bool
     */
    public static function mailrequestIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'mail_requestsenabled', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['mail_requestsenabled'] = SP_Config::getValue('mail_requestsenabled', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Comprobar si está habilitado LDAP
     * @return bool
     */
    public static function ldapIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'ldap_enabled', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['ldap_enabled'] = SP_Config::getValue('ldap_enabled', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Comprobar si está habilitado el log de eventos
     * @return bool
     */
    public static function logIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'log_enabled', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['log_enabled'] = SP_Config::getValue('log_enabled', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Comprobar si está habilitado el formato de tarjeta en los resultados
     * @return bool
     */
    public static function resultsCardsIsEnabled()
    {
        $enabled = SP_Common::parseParams('s', 'resultsascards', 0);
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($enabled === 0 || $reload === 1) {
            $enabled = $_SESSION['resultsascards'] = SP_Config::getValue('resultsascards', false);
        }

        return self::boolval($enabled);
    }

    /**
     * @brief Establecer variable de sesión para recargar la aplicación
     * @return none
     */
    public static function reload()
    {
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($reload === 0) {
            $_SESSION["reload"] = 1;
        }
    }

    /**
     * @brief Comprobar si se necesita recargar la aplicación
     * @return none
     */
    public static function checkReload()
    {
        $reload = SP_Common::parseParams('s', 'reload', 0);

        if ($reload === 1) {
            $_SESSION['reload'] = 0;
            //exit("<script>window.location.href = 'index.php';</script>");
            exit("<script>location.reload();</script>");
        }
    }

    /**
     * @brief Devolver al navegador archivos CSS y JS comprimidos
     * @param string $type tipo de recurso a devolver
     * @param array $files archivos a parsear
     * @return none
     *
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     */
    public static function getMinified($type, &$files)
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

        $path = SP_Init::$SERVERROOT . DIRECTORY_SEPARATOR;

        if ($type == 'js') {
            header("Content-type: application/x-javascript; charset: UTF-8");
        } elseif ($type == 'css') {
            header("Content-type: text/css; charset: UTF-8");
        }

        flush();
        ob_start('ob_gzhandler');

        foreach ($files as $file) {
            $filePath = $path . $file['href'];

            if ($file['min'] === true) {
                echo '/* MINIFIED FILE: ' . $file['href'] . ' */' . PHP_EOL;
                if ($type == 'js') {
                    echo self::jsCompress(file_get_contents($filePath));
                } elseif ($type == 'css') {
                    require_once EXTENSIONS_DIR . DIRECTORY_SEPARATOR . 'cssmin' . DIRECTORY_SEPARATOR . 'class.cssmin.php';
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
     * @brief Calcular el hash MD5 de varios archivos
     * @param array $files archivos a calcular
     * @return string
     */
    private static function getEtag(&$files)
    {
        $md5Sum = '';
        $path = SP_Init::$SERVERROOT . DIRECTORY_SEPARATOR;

        foreach ($files as $file) {
            $md5Sum .= md5_file($path . $file['href']);
        }

        return md5($md5Sum);
    }

    /**
     * @brief Devolver las cabeceras enviadas desde el cliente
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
     * @brief Comprimir código javascript
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

    /** Checks a variable to see if it should be considered a boolean true or false.
     *     Also takes into account some text-based representations of true of false,
     *     such as 'false','N','yes','on','off', etc.
     * @author Samuel Levy <sam+nospam@samuellevy.com>
     * @param mixed $in The variable to check
     * @param bool $strict If set to false, consider everything that is not false to
     *                     be true.
     * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
     */
    public static function boolval($in, $strict = false)
    {
        $out = null;
        $in = (is_string($in) ? strtolower($in) : $in);
        // if not strict, we only have to check if something is false
        if (in_array($in, array('false', 'no', 'n', '0', 'off', false, 0), true) || !$in) {
            $out = false;
        } else if ($strict) {
            // if strict, check the equivalent true values
            if (in_array($in, array('true', 'yes', 'y', '1', 'on', true, 1), true)) {
                $out = true;
            }
        } else {
            // not strict? let the regular php bool check figure it out (will
            // largely default to true)
            $out = ($in ? true : false);
        }
        return $out;
    }
}