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

namespace SP\Http;

use Klein\DataCollection\DataCollection;
use Klein\Klein;
use SP\Bootstrap;
use SP\Core\Crypt\CryptPKI;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\Html\Html;
use SP\Util\Filter;
use SP\Util\Util;

/**
 * Clase Request para la gestión de peticiones HTTP
 *
 * @package SP
 */
class Request
{
    /**
     * @var array Directorios seguros para include
     */
    const SECURE_DIRS = ['css', 'js'];
    /**
     * @var \Klein\Request
     */
    private $request;
    /**
     * @var DataCollection
     */
    private $params;

    /**
     * Request constructor.
     *
     * @param Klein $klein
     */
    public function __construct(Klein $klein)
    {
        $this->request = $klein->request();
        $this->params = $this->getParamsByMethod();
    }

    /**
     * @return DataCollection
     */
    private function getParamsByMethod()
    {
        if ($this->request->method('GET')) {
            return $this->request->paramsGet();
        } else {
            return $this->request->paramsPost();
        }
    }

    /**
     * Devolver las cabeceras enviadas desde el cliente.
     *
     * @param string $header nombre de la cabecera a devolver
     *
     * @return array|string
     */
    public static function getRequestHeaders($header = '')
    {
        if (!empty($header)) {
            $header = strpos($header, 'HTTP_') === false ? 'HTTP_' . str_replace('-', '_', strtoupper($header)) : $header;

            return isset($_SERVER[$header]) ? $_SERVER[$header] : '';
        }

        return self::getApacheHeaders();
    }

    /**
     * Función que sustituye a apache_request_headers
     *
     * @return array
     */
    private static function getApacheHeaders()
    {
        if (function_exists('\apache_request_headers')) {
            return apache_request_headers();
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $key = ucwords(strtolower(str_replace('_', '-', substr($key, 5))), '-');
                $headers[$key] = $value;
            } else {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Obtener los valores de variables $_GET y $_POST
     * y devolverlos limpios con el tipo correcto o esperado.
     *
     * @param string $param    con el parámetro a consultar
     * @param mixed  $default  valor por defecto a devolver
     * @param bool   $check    comprobar si el parámetro está presente
     * @param mixed  $force    valor devuelto si el parámeto está definido
     * @param bool   $sanitize escapar/eliminar carácteres especiales
     *
     * @return mixed si está presente el parámeto en la petición devuelve bool. Si lo está, devuelve el valor.
     * @deprecated
     */
    public static function analyze($param, $default = '', $check = false, $force = false, $sanitize = true)
    {
        if (!isset($_REQUEST[$param])) {
            return $force ? !$force : $default;
        }

        if ($check) {
            return true;
        }

        if ($force) {
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
     *
     * @return mixed
     * @deprecated
     */
    public static function parse(&$value, $default, $sanitize)
    {
        if (is_array($value)) {
            foreach ($value as &$data) {
                $data = self::parse($data, $default, $sanitize);
            }

            return $value;
        }

        if ((is_numeric($value) || is_numeric($default))
            && !is_string($default)
        ) {
            return (int)$value;
        }

        if (is_string($value)
        ) {
            return ($sanitize === true) ? Html::sanitize($value) : (string)$value;
        }

        return $value;
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
     *
     * @return string
     */
    public static function getSecureAppFile($file, $base = null)
    {
        return basename(self::getSecureAppPath($file, $base));
    }

    /**
     * Devolver una ruta segura para
     *
     * @param        $path
     * @param string $base
     *
     * @return string
     */
    public static function getSecureAppPath($path, $base = null)
    {
        if ($base === null) {
            $base = APP_ROOT;
        } elseif (!in_array(basename($base), self::SECURE_DIRS, true)) {
            return '';
        }

        $realPath = realpath($base . DIRECTORY_SEPARATOR . $path);

        if ($realPath === false
            || strpos($realPath, $base) !== 0
        ) {
            return '';
        }

        return $realPath;
    }

    /**
     * Comprobar si se realiza una recarga de la página
     *
     * @return bool
     */
    public function checkReload()
    {
        return $this->request->headers()->get('Cache-Control') === 'max-age=0';
    }

    /**
     * @param $param
     * @param $default
     *
     * @return string
     * @deprecated
     */
    public function analyzeEmail($param, $default = null)
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getEmail($this->params->get($param));
    }

    /**
     * Analizar un valor encriptado y devolverlo desencriptado
     *
     * @param $param
     *
     * @return string
     */
    public function analyzeEncrypted($param)
    {
        $encryptedData = $this->analyzeString($param);

        if ($encryptedData === null) {
            return '';
        }

        try {
            // Desencriptar con la clave RSA
            $clearData = Bootstrap::getContainer()->get(CryptPKI::class)
                ->decryptRSA(base64_decode($encryptedData));

            // Desencriptar con la clave RSA
            if ($clearData === false) {
                debugLog('No RSA encrypted data from request');

                return $encryptedData;
            }

            return $clearData;
        } catch (\Exception $e) {
            processException($e);

            return $encryptedData;
        }
    }

    /**
     * @param $param
     * @param $default
     *
     * @return string
     */
    public function analyzeString($param, $default = null)
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getString($this->params->get($param));
    }

    /**
     * @param string        $param
     * @param callable|null $mapper
     * @param mixed         $default
     *
     * @return mixed
     */
    public function analyzeArray($param, callable $mapper = null, $default = null)
    {
        if ($this->params->exists($param)
            && is_array($this->params->get($param))
        ) {
            if (is_callable($mapper)) {
                return $mapper($this->params->get($param));
            }

            return array_map(function ($value) {
                return is_numeric($value) ? $this->analyzeInt($value) : $this->analyzeString($value);
            }, $this->params->get($param));
        }

        return $default;
    }

    /**
     * @param $param
     * @param $default
     *
     * @return int
     */
    public function analyzeInt($param, $default = null): int
    {
        if (!$this->params->exists($param)) {
            return (int)$default;
        }

        return Filter::getInt($this->params->get($param));
    }

    /**
     * Comprobar si la petición es en formato JSON
     *
     * @return bool
     */
    public function isJson()
    {
        return strpos($this->request->headers()->get('Accept'), 'application/json') !== false;
    }

    /**
     * Comprobar si la petición es Ajax
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->request->headers()->get('X-Requested-With') === 'XMLHttpRequest'
            || $this->analyzeInt('isAjax', 0) === 1;
    }

    /**
     * @param string $file
     *
     * @return array|null
     */
    public function getFile(string $file)
    {
        return $this->request->files()->get($file);
    }

    /**
     * @param $param
     * @param $default
     *
     * @return bool
     */
    public function analyzeBool($param, $default = null)
    {
        if (!$this->params->exists($param)) {
            return (bool)$default;
        }

        return Util::boolval($this->params->get($param));
    }

    /**
     * @param string $key
     * @param string $param Checks the signature only for the given param
     *
     * @throws SPException
     */
    public function verifySignature($key, $param = null)
    {
        $result = false;

        if (($hash = $this->params->get('h')) !== null) {
            if ($param === null) {
                $uri = str_replace('&h=' . $hash, '', $this->request->uri());
                $uri = substr($uri, strpos($uri, '?') + 1);
            } else {
                $uri = $this->params->get($param, '');
            }

            $result = Hash::checkMessage($uri, $key, $hash);
        }

        if ($result === false) {
            throw new SPException('URI string altered');
        }
    }
}