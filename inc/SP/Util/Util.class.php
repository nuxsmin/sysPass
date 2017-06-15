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

use Defuse\Crypto\Core;
use Defuse\Crypto\Encoding;
use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Session;
use SP\Html\Html;
use SP\Http\Request;
use SP\Log\Log;
use SP\Log\LogUtil;

defined('APP_ROOT') || die();

/**
 * Clase con utilizades para la aplicación
 */
class Util
{
    /**
     * Generar una clave aleatoria
     *
     * @param int $length Longitud de la clave
     * @param bool $useNumbers Usar números
     * @param bool $useSpecial Usar carácteres especiales
     * @param bool $checKStrength
     * @return string
     */
    public static function randomPassword($length = 16, $useNumbers = true, $useSpecial = true, $checKStrength = true)
    {
        $charsLower = 'abcdefghijklmnopqrstuwxyz';
        $charsUpper = 'ABCDEFGHIJKLMNOPQRSTUWXYZ';

        $alphabet = $charsLower . $charsUpper;

        if ($useSpecial === true) {
            $charsSpecial = '@$%&/()!_:.;{}^';
            $alphabet .= $charsSpecial;
        }

        if ($useNumbers === true) {
            $charsNumbers = '0123456789';
            $alphabet .= $charsNumbers;
        }

        /**
         * @return array
         */
        $passGen = function () use ($alphabet, $length) {
            $pass = [];
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache

            for ($i = 0; $i < $length; $i++) {
                $n = mt_rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }

            return $pass;
        };

        if ($checKStrength === true) {
            do {
                $pass = $passGen();
                $strength = ['lower' => 0, 'upper' => 0, 'special' => 0, 'number' => 0];

                foreach ($pass as $char) {
                    if (strpos($charsLower, $char) !== false) {
                        $strength['lower']++;
                    } elseif (strpos($charsUpper, $char) !== false) {
                        $strength['upper']++;
                    } elseif ($useSpecial === true && strpos($charsSpecial, $char) !== false) {
                        $strength['special']++;
                    } elseif ($useNumbers === true && strpos($charsNumbers, $char) !== false) {
                        $strength['number']++;
                    }
                }

                if ($useSpecial === false) {
                    unset($strength['special']);
                }

                if ($useNumbers === false) {
                    unset($strength['number']);
                }
            } while (in_array(0, $strength, true));

            return implode($pass);
        }

        return implode($passGen());
    }

    /**
     * Generar una cadena aleatoria usuando criptografía.
     *
     * @param int $length opcional, con la longitud de la cadena
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function generateRandomBytes($length = 30)
    {
        return Encoding::binToHex(Core::secureRandom($length));
    }


    /**
     * Devuelve el valor de la variable enviada por un formulario.
     *
     * @param string $s con el nombre de la variable
     * @param string $d con el valor por defecto
     * @return string con el valor de la variable
     */
    public static function init_var($s, $d = '')
    {
        $r = $d;
        if (isset($_REQUEST[$s]) && !empty($_REQUEST[$s])) {
            $r = Html::sanitize($_REQUEST[$s]);
        }

        return $r;
    }


    /**
     * Devuelve la versión de sysPass.
     *
     * @return string con la versión
     */
    public static function getVersionString()
    {
        return '2.1';
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
        if (!Config::getConfig()->isCheckUpdates()) {
            return false;
        }

        try {
            $data = self::getDataFromUrl(self::getAppInfo('appupdates'));
        } catch (SPException $e) {
            return false;
        }

        $updateInfo = json_decode($data);

        if (!isset($updateInfo->message)) {
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
//            preg_match('/v?(\d+)\.(\d+)\.(\d+)(\-[a-z0-9.]+)?$/', $version, $realVer);

            if (is_array($realVer) && Init::isLoggedIn()) {
                $appVersion = implode('', self::getVersion(true));
                $pubVersion = $realVer[1] . $realVer[2] . $realVer[3] . $realVer[4];
//                $pubVersion = $realVer[1] . $realVer[2] . $realVer[3];

                if ((int)$pubVersion > (int)$appVersion) {
                    return [
                        'version' => $version,
                        'url' => $url,
                        'title' => $title,
                        'description' => $description,
                        'date' => $date];
                }

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Obtener datos desde una URL usando CURL
     *
     * @param string $url
     * @param array $data
     * @param bool|null $useCookie
     * @param bool $weak
     * @return bool|string
     * @throws SPException
     */
    public static function getDataFromUrl($url, array $data = null, $useCookie = false, $weak = false)
    {
        if (!Checks::curlIsAvailable()) {
            $Log = LogUtil::extensionNotLoaded('CURL', __FUNCTION__);

            throw new SPException(SPException::SP_WARNING, $Log->getDescription());
        }

        $ch = curl_init($url);

        if (Config::getConfig()->isProxyEnabled()) {
            curl_setopt($ch, CURLOPT_PROXY, Config::getConfig()->getProxyServer());
            curl_setopt($ch, CURLOPT_PROXYPORT, Config::getConfig()->getProxyPort());
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

            $proxyUser = Config::getConfig()->getProxyUser();

            if ($proxyUser) {
                $proxyAuth = $proxyUser . ':' . Config::getConfig()->getProxyPass();
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'sysPass-App');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if ($weak === true) {
            // Trust SSL enabled server
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        if (null !== $data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $data['type']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data['data']);
        }

        if ($useCookie) {
            $cookie = self::getUserCookieFile();

            if ($cookie) {
                if (!Session::getCurlCookieSession()) {
                    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
                    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);

                    Session::setCurlCookieSession(true);
                }

                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            }
        }

        $data = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($data === false || $httpStatus !== 200) {
            $Log = new Log();
            $LogMessgae = $Log->getLogMessage();
            $LogMessgae->setAction(__FUNCTION__);
            $LogMessgae->addDescription(curl_error($ch));
            $LogMessgae->addDetails(__('Respuesta', false), $httpStatus);
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();

            throw new SPException(SPException::SP_WARNING, $LogMessgae->getDescription());
        }

        return $data;
    }

    /**
     * Devuelve el nombre de archivo a utilizar para las cookies del usuario
     *
     * @return string|false
     */
    public static function getUserCookieFile()
    {
        $tempDir = self::getTempDir();

        return $tempDir ? $tempDir . DIRECTORY_SEPARATOR . md5('syspass-' . Session::getUserData()->getUserLogin()) : false;
    }

    /**
     * Comprueba y devuelve un directorio temporal válido
     *
     * @return bool|string
     */
    public static function getTempDir()
    {
        $sysTmp = sys_get_temp_dir();
        $appTmp = Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'tmp';
        $file = 'syspass.test';

        if (file_exists($appTmp . DIRECTORY_SEPARATOR . $file)) {
            return $appTmp;
        }

        if (file_exists($sysTmp . DIRECTORY_SEPARATOR . $file)) {
            return $sysTmp;
        }

        if (is_dir($appTmp) || @mkdir($appTmp)) {
            if (touch($appTmp . DIRECTORY_SEPARATOR . $file)) {
                return $appTmp;
            }
        }

        return touch($sysTmp . DIRECTORY_SEPARATOR . $file) ? $sysTmp : false;
    }

    /**
     * Devuelve información sobre la aplicación.
     *
     * @param string $index con la key a devolver
     * @return array con las propiedades de la aplicación
     */
    public static function getAppInfo($index = null)
    {
        $appinfo = [
            'appname' => 'sysPass',
            'appdesc' => 'Systems Password Manager',
            'appalias' => 'SPM',
            'appwebsite' => 'https://www.syspass.org',
            'appblog' => 'https://www.cygnux.org',
            'appdoc' => 'https://doc.syspass.org',
            'appupdates' => 'https://api.github.com/repos/nuxsmin/sysPass/releases/latest',
            'appnotices' => 'https://api.github.com/repos/nuxsmin/sysPass/issues?milestone=none&state=open&labels=Notices',
            'apphelp' => 'https://github.com/nuxsmin/sysPass/issues',
            'appchangelog' => 'https://github.com/nuxsmin/sysPass/blob/master/CHANGELOG'];

        if (null !== $index && isset($appinfo[$index])) {
            return $appinfo[$index];
        }

        return $appinfo;
    }

    /**
     * Devuelve la versión de sysPass.
     *
     * @param bool $retBuild devolver el número de compilación
     * @param bool $normalized
     *
     * @return array con el número de versión
     */
    public static function getVersion($retBuild = false, $normalized = false)
    {
        $build = 17061503;
        $version = [2, 1, 11];

        if ($normalized === true) {
            return [implode('', $version), $build];
        }

        if ($retBuild === true) {
            $version[] = $build;
        }

        return $version;
    }

    /**
     * Comprobar si hay notificaciones de sysPass disponibles desde internet (github.com)
     * Esta función hace una petición a GitHub y parsea el JSON devuelto
     *
     * @return array|bool
     */
    public static function checkNotices()
    {
        if (!Config::getConfig()->isChecknotices()) {
            return false;
        }

        try {
            $data = self::getDataFromUrl(self::getAppInfo('appnotices'));
        } catch (SPException $e) {
            return false;
        }

        $noticesData = json_decode($data);


        if (!isset($noticesData->message)) {
            $notices = [];

            // $noticesData[0]->title
            // $noticesData[0]->body
            // $noticesData[0]->created_at

            foreach ($noticesData as $notice) {
                $notices[] = [
                    $notice->title,
//              $notice->body,
                    $notice->created_at
                ];
            }

            return $notices;
        }

        debugLog($noticesData->message);

        return false;
    }

    /**
     * Realiza el proceso de logout.
     */
    public static function logout()
    {
        exit('<script>sysPassApp.actions().main.logout();</script>');
    }

    /**
     * Obtener el tamaño máximo de subida de PHP.
     */
    public static function getMaxUpload()
    {
        $max_upload = (int)ini_get('upload_max_filesize');
        $max_post = (int)ini_get('post_max_size');
        $memory_limit = (int)ini_get('memory_limit');
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        Log::writeNewLog(__FUNCTION__, 'Max. PHP upload: ' . $upload_mb . 'MB');
    }

    /**
     * Checks a variable to see if it should be considered a boolean true or false.
     * Also takes into account some text-based representations of true of false,
     * such as 'false','N','yes','on','off', etc.
     *
     * @author Samuel Levy <sam+nospam@samuellevy.com>
     * @param mixed $in The variable to check
     * @param bool $strict If set to false, consider everything that is not false to
     *                      be true.
     * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
     */
    public static function boolval($in, $strict = false)
    {
        $in = (is_string($in) ? strtolower($in) : $in);

        // if not strict, we only have to check if something is false
        if (in_array($in, array('false', 'no', 'n', '0', 'off', false, 0), true) || !$in) {
            return false;
        }

        if ($strict && in_array($in, array('true', 'yes', 'y', '1', 'on', true, 1), true)) {
            return true;
        }

        // not strict? let the regular php bool check figure it out (will
        // largely default to true)
        return ($in ? true : false);
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
            exit('<script>location.reload();</script>');
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
            $value = str_replace(['\'', '"'], '\\\'', $value);
        });
        return $array;
    }

    /**
     * Obtener la URL de acceso al servidor
     *
     * @return string
     */
    public static function getServerUrl()
    {
        $urlScheme = Checks::httpsEnabled() ? 'https://' : 'http://';
        $urlPort = ($_SERVER['SERVER_PORT'] !== 443) ? ':' . $_SERVER['SERVER_PORT'] : '';

        return $urlScheme . $_SERVER['SERVER_NAME'] . $urlPort;
    }

    /**
     * Cast an object to another class, keeping the properties, but changing the methods
     *
     * @param string $class Class name
     * @param string|object $object
     * @param string $srcClass Nombre de la clase serializada
     * @return mixed
     * @link http://blog.jasny.net/articles/a-dark-corner-of-php-class-casting/
     */
    public static function castToClass($class, $object, $srcClass = null)
    {
        if (!is_object($object)) {
            $object = unserialize($object);
        }

        if (get_class($object) === '__PHP_Incomplete_Class') {
            //  Elimina el nombre de la clase en los métodos privados
            if ($srcClass !== null) {
                $replaceSrc = preg_replace_callback(
                    '/:\d+:"\x00' . preg_quote($srcClass, '/') . '\x00(\w+)"/',
                    function ($matches) {
                        return ':' . strlen($matches[1]) . ':"' . $matches[1] . '"';
                    },
                    serialize($object)
                );
            } else {
                $replaceSrc = serialize($object);
            }

            $replace = preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', $replaceSrc);

            return unserialize($replace);
        }

        return $object;
    }

    /**
     * Devuelve la última función llamada tras un error
     *
     * @param string $function La función utilizada como base
     */
    public static function traceLastCall($function = null)
    {
        $backtrace = debug_backtrace(0);

        if (count($backtrace) === 1) {
            return $backtrace[1]['function'];
        }

        foreach ($backtrace as $index => $fn) {
            if ($fn['function'] === $function) {
                return $backtrace[$index + 1]['function'];
            }
        }
    }

    /**
     * Bloquear la aplicación
     *
     * @param bool $setMaintenance
     */
    public static function lockApp($setMaintenance = true)
    {
        ConfigDB::setValue('lock', Session::getUserData()->getUserId(), false);

        if ($setMaintenance) {
            Config::getConfig()->setMaintenance(true);
            Config::saveConfig(null, false);
        }
    }

    /**
     * Desbloquear la aplicación
     *
     * @param bool $unsetMaintenance
     */
    public static function unlockApp($unsetMaintenance = true)
    {
        ConfigDB::setValue('lock', 0, false);

        if ($unsetMaintenance) {
            Config::getConfig()->setMaintenance(false);
            Config::saveConfig(null, false);
        }
    }

    /**
     * Comprueba si la aplicación está bloqueada
     *
     * @return int
     */
    public static function getAppLock()
    {
        return (int)ConfigDB::getValue('lock', 0);
    }

    /**
     * Devolver el tiempo aproximado en segundos de una operación
     *
     * @param $startTime
     * @param $numItems
     * @param $totalItems
     *
     * @return array Con el tiempo estimado y los elementos por segundo
     */
    public static function getETA($startTime, $numItems, $totalItems)
    {
        if ($numItems > 0 && $totalItems > 0) {
            $runtime = time() - $startTime;
            $eta = (int)((($totalItems * $runtime) / $numItems) - $runtime);

            return [$eta, $numItems / $runtime];
        }

        return [0, 0];
    }

    /**
     * Devolver la dirección IP del cliente
     *
     * @param bool $fullForwarded Devolver la cadena de forward completa
     * @return string
     */
    public static function getClientAddress($fullForwarded = false)
    {
        $forwarded = Request::getRequestHeaders('X-Forwarded-For');

        if ($forwarded !== '') {
            if (preg_match_all('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $forwarded, $matches)) {
                return $fullForwarded ? implode(',', $matches[0]) : $matches[0][0];
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }
}