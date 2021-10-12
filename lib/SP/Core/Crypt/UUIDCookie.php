<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Core\Crypt;

use SP\Http\Request;

/**
 * Class SecureCookie
 *
 * @package SP\Core\Crypt
 */
class UUIDCookie extends Cookie
{
    /**
     * Nombre de la cookie
     */
    public const COOKIE_NAME = 'SYSPASS_UUID';

    public static function factory(Request $request): UUIDCookie
    {
        return new self(self::COOKIE_NAME, $request);
    }

    /**
     * Creates a cookie and sets its data
     *
     * @return string|false
     */
    public function createCookie(string $signKey)
    {
        $uuid = uniqid('', true);

        if ($this->setCookie($this->sign($uuid, $signKey))) {
            return $uuid;
        }

        return false;
    }

    /**
     * Loads cookie data
     *
     * @return false|string
     */
    public function loadCookie(string $signKey)
    {
        $data = $this->getCookie();

        return $data !== false
            ? $this->getCookieData($data, $signKey)
            : false;
    }
}