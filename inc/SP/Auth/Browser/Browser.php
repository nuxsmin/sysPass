<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Auth\Browser;

use SP\Auth\AuthInterface;
use SP\DataModel\UserLoginData;

/**
 * Class Browser
 *
 * Autentificación basada en credenciales del navegador
 *
 * @package SP\Auth\Browser
 */
class Browser implements AuthInterface
{
    /**
     * Autentificar al usuario
     *
     * @param UserLoginData $UserData Datos del usuario
     * @return BrowserAuthData
     */
    public function authenticate(UserLoginData $UserData)
    {
        $AuthData = new BrowserAuthData();
        $AuthData->setAuthenticated($this->checkServerAuthUser($UserData->getLogin()));

        return $AuthData;
    }

    /**
     * Comprobar si el usuario es autentificado por el servidor web
     *
     * @param $login string El login del usuario a comprobar
     * @return bool
     */
    public function checkServerAuthUser($login)
    {
        $authUser = $this->getServerAuthUser();

        return $authUser === null ?: $authUser === $login;
    }

    /**
     * Devolver el nombre del usuario autentificado por el servidor web
     *
     * @return string
     */
    public function getServerAuthUser()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER'])) {
            return $_SERVER['PHP_AUTH_USER'];
        } elseif (isset($_SERVER['REMOTE_USER']) && !empty($_SERVER['REMOTE_USER'])) {
            return $_SERVER['REMOTE_USER'];
        }

        return null;
    }
}