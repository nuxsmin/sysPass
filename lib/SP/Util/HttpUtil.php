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
use SP\Http\Request;

/**
 * Class HttpUtil
 *
 * @package SP\Util
 */
class HttpUtil
{
    /**
     * Comprobar y forzar (si es necesario) la conexión HTTPS
     *
     * @param ConfigData $configData
     */
    public static function checkHttps(ConfigData $configData)
    {
        if ($configData->isHttpsEnabled() && !Checks::httpsEnabled()) {
            $port = ((int)$_SERVER['SERVER_PORT'] !== 443) ? ':' . $_SERVER['SERVER_PORT'] : '';
            $host = str_replace('http', 'https', self::getHttpHost());

            header('Location: ' . $host . $port . $_SERVER['REQUEST_URI']);
        }
    }

    /**
     * Returns the URI used by the browser and checks for the protocol used
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     * @return string
     */
    public static function getHttpHost()
    {
        $forwarded = self::getForwardedData();

        // Check in style of RFC 7239
        if (null !== $forwarded) {
            return strtolower($forwarded['proto'] . '://' . $forwarded['host']);
        }

        $xForward = self::getXForwardedData();

        // Check (deprecated) de facto standard
        if (null !== $xForward) {
            return strtolower($xForward['proto'] . '://' . $xForward['host']);
        }

        // We got called directly
        if (Checks::httpsEnabled()) {
            return 'https://' . $_SERVER['HTTP_HOST'];
        }

        return 'http://' . $_SERVER['HTTP_HOST'];
    }

    /**
     * Devolver datos de forward RFC 7239
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     * @return array|null
     */
    public static function getForwardedData()
    {
        $forwarded = Request::getRequestHeaders('HTTP_FORWARDED');

        // Check in style of RFC 7239
        if ($forwarded !== ''
            && preg_match('/proto=(\w+);/i', $forwarded, $matchesProto)
            && preg_match('/host=(\w+);/i', $forwarded, $matchesHost)
        ) {
            $data = [
                'host ' => $matchesHost[0],
                'proto' => $matchesProto[0],
                'for' => self::getForwardedFor()
            ];

            // Check if protocol and host are not empty
            if (!empty($data['proto']) && !empty($data['host'])) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Devolver la dirección IP del cliente a través de proxy o directo
     *
     * @return array|string
     */
    public static function getForwardedFor()
    {
        if (preg_match_all('/for="?\[?([\w.:]+)"?\]?[,;]?/i',
            Request::getRequestHeaders('HTTP_FORWARDED'), $matchesFor)) {
            return $matchesFor[1];
        }

        $matchesFor = preg_split('/(?<=[\w])+,/i',
            Request::getRequestHeaders('HTTP_X_FORWARDED_FOR'),
            -1,
            PREG_SPLIT_NO_EMPTY);

        if (count($matchesFor) > 0) {
            return $matchesFor;
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Devolver datos de x-forward
     *
     * @return array|null
     */
    public static function getXForwardedData()
    {
        $forwardedHost = Request::getRequestHeaders('HTTP_X_FORWARDED_HOST');
        $forwardedProto = Request::getRequestHeaders('HTTP_X_FORWARDED_PROTO');

        // Check (deprecated) de facto standard
        if (!empty($forwardedHost) && !empty($forwardedProto)) {
            $data = [
                'host' => trim(str_replace('"', '', $forwardedHost)),
                'proto' => trim(str_replace('"', '', $forwardedProto)),
                'for' => self::getForwardedFor()
            ];

            // Check if protocol and host are not empty
            if (!empty($data['host']) && !empty($data['proto'])) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Devolver la dirección IP del cliente
     *
     * @param bool $fullForwarded Devolver la cadena de forward completa
     *
     * @return string|array
     */
    public static function getClientAddress($fullForwarded = false)
    {
        if (APP_MODULE === 'tests') {
            return '127.0.0.1';
        }

        $forwarded = self::getForwardedFor();

        if (is_array($forwarded)) {
            return $fullForwarded ? implode(',', $forwarded) : $forwarded[0];
        }

        return $forwarded;
    }
}