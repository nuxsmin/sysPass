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

use SP\Config\ConfigData;
use SP\Html\Html;
use SP\Http\Request;

/**
 * Class HttpUtil
 *
 * @package SP\Util
 */
final class HttpUtil
{
    /**
     * Comprobar y forzar (si es necesario) la conexión HTTPS
     *
     * @param ConfigData $configData
     * @param Request    $request
     */
    public static function checkHttps(ConfigData $configData, Request $request)
    {
        if ($configData->isHttpsEnabled() && !$request->isHttps()) {
            $serverPort = $request->getServerPort();

            $port = $serverPort !== 443 ? ':' . $serverPort : '';
            $host = str_replace('http', 'https', $request->getHttpHost());

            header('Location: ' . $host . $port . $_SERVER['REQUEST_URI']);
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
     * Comprobar si existen parámetros pasados por POST para enviarlos por GET
     */
    public static function importUrlParamsToGet()
    {
        $params = [];

        foreach ($_REQUEST as $param => $value) {
            $param = Filter::getString($param);

            if (strpos($param, 'g_') !== false) {
                $params[] = substr($param, 2) . '=' . Html::sanitize($value);
            }
        }

        return count($params) > 0 ? '?' . implode('&', $params) : '';
    }
}