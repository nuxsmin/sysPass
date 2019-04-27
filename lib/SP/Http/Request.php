<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Exception;
use Klein\DataCollection\DataCollection;
use Klein\DataCollection\HeaderDataCollection;
use SP\Bootstrap;
use SP\Core\Crypt\CryptPKI;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\Util\Filter;
use SP\Util\Util;

/**
 * Clase Request para la gestión de peticiones HTTP
 *
 * @package SP
 */
final class Request
{
    /**
     * @var array Directorios seguros para include
     */
    const SECURE_DIRS = ['css', 'js'];
    /**
     * @var HeaderDataCollection
     */
    private $headers;
    /**
     * @var \Klein\Request
     */
    private $request;
    /**
     * @var DataCollection
     */
    private $params;
    /**
     * @var string
     */
    private $method;
    /**
     * @var bool
     */
    private $https;

    /**
     * Request constructor.
     *
     * @param \Klein\Request $request
     */
    public function __construct(\Klein\Request $request)
    {
        $this->request = $request;
        $this->headers = $this->request->headers();
        $this->params = $this->getParamsByMethod();
        $this->detectHttps();
    }

    /**
     * @return DataCollection
     */
    private function getParamsByMethod()
    {
        if ($this->request->method('GET')) {
            $this->method = 'GET';
            return $this->request->paramsGet();
        } else {
            $this->method = 'POST';
            return $this->request->paramsPost();
        }
    }

    /**
     * Detects if the connection is done through HTTPS
     */
    private function detectHttps()
    {
        $this->https = Util::boolval($this->request->server()->get('HTTPS', 'off'))
            || $this->request->server()->get('SERVER_PORT', 0) === 443;
    }

    /**
     * Devuelve un nombre de archivo seguro
     *
     * @param string $file
     * @param string $base
     *
     * @return string
     */
    public static function getSecureAppFile(string $file, string $base = null)
    {
        return basename(self::getSecureAppPath($file, $base));
    }

    /**
     * Devolver una ruta segura para
     *
     * @param string $path
     * @param string $base
     *
     * @return string
     */
    public static function getSecureAppPath(string $path, string $base = null)
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
     * @param bool $fullForwarded
     *
     * @return array|string
     */
    public function getClientAddress(bool $fullForwarded = false)
    {
        if (APP_MODULE === 'tests') {
            return '127.0.0.1';
        }

        $forwarded = $this->getForwardedFor();

        if ($forwarded !== null) {
            return $fullForwarded ? implode(',', $forwarded) : $forwarded[0];
        }

        return $this->request->server()->get('REMOTE_ADDR', '');
    }

    /**
     * @return string[]|null
     */
    public function getForwardedFor()
    {
        // eg: Forwarded: by=<identifier>; for=<identifier>; host=<host>; proto=<http|https>
        $forwarded = $this->headers->get('HTTP_FORWARDED');

        if ($forwarded !== null &&
            preg_match_all('/(?:for=([\w.:]+))|(?:for="\[([\w.:]+)\]")/i',
                $forwarded, $matches)
        ) {
            return array_filter(array_merge($matches[1], $matches[2]), function ($value) {
                return !empty($value);
            });
        }

        // eg: X-Forwarded-For: 192.0.2.43, 2001:db8:cafe::17
        $xForwarded = $this->headers->exists('HTTP_X_FORWARDED_FOR');

        if ($xForwarded !== null) {
            $matches = preg_split('/(?<=[\w])+,\s?/i',
                $xForwarded,
                -1,
                PREG_SPLIT_NO_EMPTY);

            if (count($matches) > 0) {
                return $matches;
            }
        }

        return null;
    }

    /**
     * Comprobar si se realiza una recarga de la página
     *
     * @return bool
     */
    public function checkReload()
    {
        return $this->headers->get('Cache-Control') === 'max-age=0';
    }

    /**
     * @param string $param
     * @param string $default
     *
     * @return string|null
     */
    public function analyzeEmail(string $param, string $default = null)
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getEmail($this->params->get($param));
    }

    /**
     * Analizar un valor encriptado y devolverlo desencriptado
     *
     * @param string $param
     *
     * @return string
     */
    public function analyzeEncrypted(string $param)
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
                logger('No RSA encrypted data from request');

                return $encryptedData;
            }

            return $clearData;
        } catch (Exception $e) {
            processException($e);

            return $encryptedData;
        }
    }

    /**
     * @param $param
     * @param $default
     *
     * @return string|null
     */
    public function analyzeString(string $param, string $default = null)
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getString($this->params->get($param));
    }

    /**
     * @param $param
     * @param $default
     *
     * @return string|null
     */
    public function analyzeUnsafeString(string $param, string $default = null)
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getRaw($this->params->get($param));
    }

    /**
     * @param string        $param
     * @param callable|null $mapper
     * @param mixed         $default
     *
     * @return array|null
     */
    public function analyzeArray(string $param, callable $mapper = null, $default = null)
    {
        $requestValue = $this->params->get($param);

        if ($requestValue !== null
            && is_array($requestValue)
        ) {
            if (is_callable($mapper)) {
                return $mapper($requestValue);
            }

            return array_map(function ($value) {
                return is_numeric($value) ? Filter::getInt($value) : Filter::getString($value);
            }, $requestValue);
        }

        return $default;
    }

    /**
     * Comprobar si la petición es en formato JSON
     *
     * @return bool
     */
    public function isJson()
    {
        return strpos($this->headers->get('Accept'), 'application/json') !== false;
    }

    /**
     * Comprobar si la petición es Ajax
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->headers->get('X-Requested-With') === 'XMLHttpRequest'
            || $this->analyzeInt('isAjax', 0) === 1;
    }

    /**
     * @param string $param
     * @param int    $default
     *
     * @return int
     */
    public function analyzeInt(string $param, int $default = null)
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getInt($this->params->get($param));
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
     * @param string $param
     * @param bool   $default
     *
     * @return bool
     */
    public function analyzeBool(string $param, bool $default = null)
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
    public function verifySignature(string $key, string $param = null)
    {
        $result = false;
        $hash = $this->params->get('h');

        if ($hash !== null) {
            // Strips out the hash param from the URI to get the
            // route which will be checked against the computed HMAC
            if ($param === null) {
                $uri = str_replace('&h=' . $hash, '', $this->request->uri());
                $uri = substr($uri, strpos($uri, '?') + 1);
            } else {
                $uri = $this->params->get($param, '');
            }

            $result = Hash::checkMessage($uri, $key, $hash);
        }

        if ($result === false) {
            throw new SPException(
                'URI string altered',
                SPException::ERROR,
                null,
                1
            );
        }
    }

    /**
     * Returns the URI used by the browser and checks for the protocol used
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     * @return string
     */
    public function getHttpHost(): string
    {
        $forwarded = $this->getForwardedData();

        // Check in style of RFC 7239
        if (null !== $forwarded) {
            return strtolower($forwarded['proto'] . '://' . $forwarded['host']);
        }

        $xForward = $this->getXForwardedData();

        // Check (deprecated) de facto standard
        if (null !== $xForward) {
            return strtolower($xForward['proto'] . '://' . $xForward['host']);
        }

        $protocol = 'http://';

        // We got called directly
        if ($this->https) {
            $protocol = 'https://';
        }

        return $protocol . $this->request->server()->get('HTTP_HOST');
    }

    /**
     * Devolver datos de forward RFC 7239
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     * @return array|null
     */
    public function getForwardedData()
    {
        $forwarded = $this->getHeader('HTTP_FORWARDED');

        // Check in style of RFC 7239
        if (!empty($forwarded)
            && preg_match('/proto=(\w+);/i', $forwarded, $matchesProto)
            && preg_match('/host=(\w+);/i', $forwarded, $matchesHost)
        ) {
            $data = [
                'host ' => $matchesHost[0],
                'proto' => $matchesProto[0],
                'for' => $this->getForwardedFor()
            ];

            // Check if protocol and host are not empty
            if (!empty($data['proto']) && !empty($data['host'])) {
                return $data;
            }
        }

        return null;
    }

    /**
     * @param string $header
     *
     * @return string
     */
    public function getHeader(string $header): string
    {
        return $this->headers->get($header, '');
    }

    /**
     * Devolver datos de x-forward
     *
     * @return array|null
     */
    public function getXForwardedData()
    {
        $forwardedHost = $this->getHeader('HTTP_X_FORWARDED_HOST');
        $forwardedProto = $this->getHeader('HTTP_X_FORWARDED_PROTO');

        // Check (deprecated) de facto standard
        if (!empty($forwardedHost) && !empty($forwardedProto)) {
            $data = [
                'host' => trim(str_replace('"', '', $forwardedHost)),
                'proto' => trim(str_replace('"', '', $forwardedProto)),
                'for' => $this->getForwardedFor()
            ];

            // Check if protocol and host are not empty
            if (!empty($data['host']) && !empty($data['proto'])) {
                return $data;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return bool
     */
    public function isHttps(): bool
    {
        return $this->https;
    }

    /**
     * @return int
     */
    public function getServerPort(): int
    {
        return (int)$this->request->server()->get('SERVER_PORT', 80);
    }

    /**
     * @return \Klein\Request
     */
    public function getRequest(): \Klein\Request
    {
        return $this->request;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getServer(string $key): string
    {
        return (string)$this->request->server()->get($key, '');
    }
}