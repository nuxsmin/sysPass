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

namespace SP\Providers\Auth\Browser;

use SP\Config\ConfigData;
use SP\DataModel\UserLoginData;
use SP\Providers\Auth\AuthInterface;

/**
 * Class Browser
 *
 * Autentificación basada en credenciales del navegador
 *
 * @package SP\Providers\Auth\Browser
 */
final class Browser implements AuthInterface
{
    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * Browser constructor.
     *
     * @param ConfigData $configData
     */
    public function __construct(ConfigData $configData)
    {
        $this->configData = $configData;
    }

    /**
     * Autentificar al usuario
     *
     * @param UserLoginData $userLoginData Datos del usuario
     *
     * @return BrowserAuthData
     */
    public function authenticate(UserLoginData $userLoginData)
    {
        $browserAuthData = new BrowserAuthData();
        $browserAuthData->setAuthGranted($this->isAuthGranted());

        if (!empty($userLoginData->getLoginUser()) && !empty($userLoginData->getLoginPass())) {
            return $browserAuthData->setAuthenticated($this->checkServerAuthUser($userLoginData->getLoginUser()));
        }

        if ($this->configData->isAuthBasicAutoLoginEnabled()) {
            $authUser = self::getServerAuthUser();
            $authPass = $this->getAuthPass();

            if ($authUser !== null && $authPass !== null) {
                $userLoginData->setLoginUser($authUser);
                $userLoginData->setLoginPass($authPass);

                $browserAuthData->setName($authUser);
                return $browserAuthData->setAuthenticated(true);
            }

            return $browserAuthData->setAuthenticated(false);
        }

        return $browserAuthData->setAuthenticated($this->checkServerAuthUser($userLoginData->getLoginUser()));
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return boolean
     */
    public function isAuthGranted()
    {
        return $this->configData->isAuthBasicAutoLoginEnabled();
    }

    /**
     * Comprobar si el usuario es autentificado por el servidor web
     *
     * @param $login string El login del usuario a comprobar
     *
     * @return bool|null
     */
    public function checkServerAuthUser($login)
    {
        $domain = $this->configData->getAuthBasicDomain();

        if (!empty($domain)) {
            $login = self::getServerAuthUser() . '@' . $domain;
        }

        $authUser = self::getServerAuthUser();

        return $authUser !== null && $authUser === $login ?: null;
    }

    /**
     * Devolver el nombre del usuario autentificado por el servidor web
     *
     * @return string
     */
    public static function getServerAuthUser()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER'])) {
            return $_SERVER['PHP_AUTH_USER'];
        }

        if (isset($_SERVER['REMOTE_USER']) && !empty($_SERVER['REMOTE_USER'])) {
            return $_SERVER['REMOTE_USER'];
        }

        return null;
    }

    /**
     * Devolver la clave del usuario autentificado por el servidor web
     *
     * @return string|null
     */
    protected function getAuthPass()
    {
        if (isset($_SERVER['PHP_AUTH_PW']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            return $_SERVER['PHP_AUTH_PW'];
        }

        return null;
    }
}