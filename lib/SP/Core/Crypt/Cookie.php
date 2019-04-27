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

namespace SP\Core\Crypt;

use SP\Bootstrap;
use SP\Http\Request;

/**
 * Class Cookie
 *
 * @package SP\Core\Crypt
 */
abstract class Cookie
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var string
     */
    private $cookieName;

    /**
     * Cookie constructor.
     *
     * @param string  $cookieName
     * @param Request $request
     */
    protected function __construct($cookieName, Request $request)
    {
        $this->cookieName = $cookieName;
        $this->request = $request;
    }

    /**
     * Firmar la cookie para autentificación
     *
     * @param string $data
     * @param string $cypher
     *
     * @return string
     */
    public final function sign($data, $cypher)
    {
        $data = base64_encode($data);

        return Hash::signMessage($data, $cypher) . ';' . $data;
    }

    /**
     * Comprobar la firma de la cookie y devolver los datos
     *
     * @param string $data
     * @param string $cypher
     *
     * @return bool|string
     */
    public final function getCookieData($data, $cypher)
    {
        if (strpos($data, ';') === false) {
            return false;
        }

        list($signature, $data) = explode(';', $data, 2);

        return Hash::checkMessage($data, $cypher, $signature) ? base64_decode($data) : false;
    }

    /**
     * Returns cookie raw data
     *
     * @return bool|string
     */
    protected function getCookie()
    {
        return $this->request->getRequest()->cookies()->get($this->cookieName, false);
    }

    /**
     * Sets cookie data
     *
     * @param $data
     *
     * @return bool
     */
    protected function setCookie($data)
    {
        // Do not try to set cookies when testing
        if (APP_MODULE === 'tests') {
            return true;
        }

        if (headers_sent()) {
            logger('Headers already sent', 'ERROR');

            return false;
        }

        return setcookie($this->cookieName, $data, 0, Bootstrap::$WEBROOT);
    }
}