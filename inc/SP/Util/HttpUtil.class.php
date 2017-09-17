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

/**
 * Class HttpUtil
 *
 * @package SP\Util
 */
class HttpUtil
{
    /**
     * Comprobar y forzar (si es necesario) la conexión HTTPS
     */
    public static function checkHttps()
    {
        if (Checks::forceHttpsIsEnabled() && !Checks::httpsEnabled()) {
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
        // Check in style of RFC 7239
        if (isset($_SERVER['HTTP_FORWARDED'])
            && preg_match('/proto=(\w+);/i', $_SERVER['HTTP_FORWARDED'], $matchesProto)
            && preg_match('/host=(\w+);/i', $_SERVER['HTTP_FORWARDED'], $matchesHost)
        ) {
            // Removes possible `"`-chars
            $protocol = strtolower($matchesProto[0]);
            $host = strtolower($matchesHost[0]);

            // Check if prtocol and host are not empty
            if (strlen($protocol) > 0 && strlen($host) > 0) {
                return $protocol . '://' . $host;
            }
        }

        // Check (deprecated) de facto standard
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'], $_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            // This only could be http or https
            $protocol = str_replace('"', '', trim($_SERVER['HTTP_X_FORWARDED_PROTO']));

            // This may be example.com or sub.example.com/syspass
            $host = str_replace('"', '', trim($_SERVER['HTTP_X_FORWARDED_HOST']));

            // Check if protocol and host are not empty
            if (strlen($protocol) > 0 && strlen($host) > 0) {
                return $protocol . '://' . $host;
            }
        }

        // We got called directly
        if (Checks::httpsEnabled()) {
            return 'https://' . $_SERVER['HTTP_HOST'];
        }

        return 'http://' . $_SERVER['HTTP_HOST'];
    }
}