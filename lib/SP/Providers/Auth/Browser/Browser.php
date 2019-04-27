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

namespace SP\Providers\Auth\Browser;

use SP\Config\ConfigData;
use SP\DataModel\UserLoginData;
use SP\Http\Request;
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
     * @var Request
     */
    private $request;

    /**
     * Browser constructor.
     *
     * @param ConfigData $configData
     * @param Request    $request
     */
    public function __construct(ConfigData $configData, Request $request)
    {
        $this->configData = $configData;
        $this->request = $request;
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
            $authUser = $this->getServerAuthUser();
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
        $authUser = $this->getServerAuthUser();

        if (!empty($domain) && !empty($authUser)) {
            $login = $authUser . '@' . $domain;
        }

        return $authUser !== null && $authUser === $login ?: null;
    }

    /**
     * Devolver el nombre del usuario autentificado por el servidor web
     *
     * @return string
     */
    public function getServerAuthUser()
    {
        $authUser = $this->request->getServer('PHP_AUTH_USER');

        if (!empty($authUser)) {
            return $authUser;
        }

        $remoteUser = $this->request->getServer('REMOTE_USER');

        if (!empty($remoteUser)) {
            return $remoteUser;
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
        $authPass = $this->request->getServer('PHP_AUTH_PW');

        return !empty($authPass) ? $authPass : null;
    }
}