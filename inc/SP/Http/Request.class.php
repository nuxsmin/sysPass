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

namespace SP\Http;

use SP\Core\CryptPKI;
use SP\Core\Init;
use SP\Html\Html;

/**
 * Clase Request para la gestión de peticiones HTTP
 *
 * @package SP
 */
class Request
{
    /**
     * Comprobar el método utilizado para enviar un formulario.
     *
     * @param string $method con el método utilizado.
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function checkReferer($method)
    {
        if (!isset($_SERVER['HTTP_REFERER'])
            || $_SERVER['REQUEST_METHOD'] !== strtoupper($method)
            || !preg_match('#' . Init::$WEBROOT . '/.*$#', $_SERVER['HTTP_REFERER'])
        ) {
            Init::initError(__('No es posible acceder directamente a este archivo'));
            exit();
        }
    }

    /**
     * Analizar un valor encriptado y devolverlo desencriptado
     *
     * @param $param
     * @return string
     */
    public static function analyzeEncrypted($param)
    {
        $encryptedData = self::analyze($param, '', false, false, false);

        if ($encryptedData === '') {
            return '';
        }

        try {
            // Desencriptar con la clave RSA
            $CryptPKI = new CryptPKI();
            $clearData = $CryptPKI->decryptRSA(base64_decode($encryptedData));
        } catch (\Exception $e) {
            debugLog($e->getMessage());
            return $encryptedData;
        }

        return $clearData;
    }

    /**
     * Obtener los valores de variables $_GET y $_POST
     * y devolverlos limpios con el tipo correcto o esperado.
     *
     * @param string $param con el parámetro a consultar
     * @param mixed $default valor por defecto a devolver
     * @param bool $check comprobar si el parámetro está presente
     * @param mixed $force valor devuelto si el parámeto está definido
     * @param bool $sanitize escapar/eliminar carácteres especiales
     * @return mixed si está presente el parámeto en la petición devuelve bool. Si lo está, devuelve el valor.
     */
    public static function analyze($param, $default = '', $check = false, $force = false, $sanitize = true)
    {
        if (!isset($_REQUEST[$param])) {
            return $force ? !$force : $default;
        } elseif ($check) {
            return true;
        } elseif ($force) {
            return $force;
        }

        return self::parse($_REQUEST[$param], $default, $sanitize);
    }

    /**
     * Devolver el valor con el tipo correcto o requerido.
     *
     * @param $value     mixed  valor a analizar
     * @param $default   mixed  tipo por defecto a devolver
     * @param $sanitize  bool   limpiar una cadena de caracteres
     * @return mixed
     */
    public static function parse(&$value, $default, $sanitize)
    {
        if (is_array($value)) {
            foreach ($value as &$data) {
                $data = self::parse($data, $default, $sanitize);
            }

            return $value;
        } elseif ((is_numeric($value) || is_numeric($default))
            && !is_string($default)
        ) {
            return (int)$value;
        } elseif (is_string($value)) {
            return ($sanitize === true) ? Html::sanitize($value) : (string)$value;
        }

        return $value;
    }

    /**
     * Comprobar si se realiza una recarga de la página
     *
     * @return bool
     */
    public static function checkReload()
    {
        return (self::getRequestHeaders('Cache-Control') === 'max-age=0');
    }

    /**
     * Devolver las cabeceras enviadas desde el cliente.
     *
     * @param string $header nombre de la cabecera a devolver
     * @return array|string
     */
    public static function getRequestHeaders($header = '')
    {
        $headers = self::getApacheHeaders();

        if (!empty($header)) {
            return array_key_exists($header, $headers) ? trim($headers[$header]) : '';
        }

        return $headers;
    }

    /**
     * Función que sustituye a apache_request_headers
     *
     * @return array
     */
    public static function getApacheHeaders()
    {
        if (function_exists('\apache_request_headers')) {
            return apache_request_headers();
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$key] = $value;
            } else {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Comprobar si existen parámetros pasados por POST para enviarlos por GET
     */
    public static function importUrlParamsToGet()
    {
        $params = [];

        foreach ($_REQUEST as $param => $value) {
            Html::sanitize($param);
            Html::sanitize($value);

            if (strpos($param, 'g_') !== false) {
                $params[] = substr($param, 2) . '=' . $value;
            }
        }

        return count($params) > 0 ? '?' . implode('&', $params) : '';
    }

    /**
     * Devuelve un nombre de archivo seguro
     *
     * @param      $file
     * @param null $base
     * @return string
     */
    public static function getSecureAppFile($file, $base = null)
    {
        return basename(self::getSecureAppPath($file, $base));
    }

    /**
     * Devolver una ruta segura para
     *
     * @param      $path
     * @param null $base
     * @return string
     */
    public static function getSecureAppPath($path, $base = null)
    {
        if ($base === null) {
            $base = Init::$SERVERROOT;
        }

        $realPath = realpath($base . DIRECTORY_SEPARATOR . $path);

        if ($realPath === false || strpos($realPath, $base) !== 0) {
            return '';
        } else {
            return $realPath;
        }
    }
}