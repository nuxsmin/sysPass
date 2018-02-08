<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Providers\Auth;

use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserLoginData;
use SP\Providers\Auth\Browser\Browser;
use SP\Providers\Auth\Browser\BrowserAuthData;
use SP\Providers\Auth\Database\Database;
use SP\Providers\Auth\Database\DatabaseAuthData;
use SP\Providers\Auth\Ldap\LdapAuthData;
use SP\Providers\Auth\Ldap\LdapMsAds;
use SP\Providers\Auth\Ldap\LdapStd;

defined('APP_ROOT') || die();

/**
 * Class Auth
 *
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 *
 * @package SP\Providers\Auth
 */
class Auth
{
    /**
     * @var array
     */
    protected $auths = [];
    /**
     * @var UserLoginData
     */
    protected $userLoginData;
    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * Auth constructor.
     *
     * @param UserLoginData $userLoginData
     * @param ConfigData    $configData
     * @throws SPException
     */
    public function __construct(UserLoginData $userLoginData, ConfigData $configData)
    {
        $this->userLoginData = $userLoginData;
        $this->configData = $configData;

        if ($this->configData->isAuthBasicEnabled()) {
            $this->registerAuth('authBrowser');
        }

        if ($this->configData->isLdapEnabled()) {
            $this->registerAuth('authLdap');
        }

        $this->registerAuth('authDatabase');
    }

    /**
     * Registrar un método de autentificación primarios
     *
     * @param string $auth Función de autentificación
     * @throws SPException
     */
    protected function registerAuth($auth)
    {
        if (array_key_exists($auth, $this->auths)) {
            throw new SPException(__u('Método ya inicializado'), SPException::ERROR, __FUNCTION__);
        }

        if (!method_exists($this, $auth)) {
            throw new SPException(__u('Método no disponible'), SPException::ERROR, __FUNCTION__);
        }

        $this->auths[$auth] = $auth;
    }

    /**
     * Probar los métodos de autentificación
     *
     * @return bool|array
     */
    public function doAuth()
    {
        $auths = [];

        /** @var AuthDataBase $pAuth */
        foreach ($this->auths as $pAuth) {
            $pResult = $this->$pAuth();

            if ($pResult !== false) {
                $auths[] = new AuthResult($pAuth, $pResult);
            }
        }

        return (count($auths) > 0) ? $auths : false;
    }

    /**
     * Autentificación de usuarios con LDAP.
     *
     * @return bool|LdapAuthData
     */
    public function authLdap()
    {
        $ldap = $this->configData->isLdapAds() ? new LdapMsAds() : new LdapStd();

        $ldapAuthData = $ldap->getLdapAuthData();

        if (!$ldap->authenticate($this->userLoginData)) {
            return $ldapAuthData->getAuthenticated() === 1 ? $ldapAuthData : false;
        }

        // Comprobamos si la cuenta está bloqueada o expirada
        if ($ldapAuthData->getExpire() > 0) {
            $ldapAuthData->setStatusCode(701);
        } elseif (!$ldapAuthData->isInGroup()) {
            $ldapAuthData->setStatusCode(702);
        }

        $ldapAuthData->setAuthenticated(1);

        return $ldapAuthData;
    }

    /**
     * Autentificación de usuarios con base de datos
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @return DatabaseAuthData
     * @throws \ReflectionException
     * @throws \SP\Core\Dic\ContainerException
     */
    public function authDatabase()
    {
        return (new Database())->authenticate($this->userLoginData);
    }

    /**
     * Autentificación de usuario con credenciales del navegador
     *
     * @return BrowserAuthData
     */
    public function authBrowser()
    {
        return (new Browser())->authenticate($this->userLoginData);
    }
}
