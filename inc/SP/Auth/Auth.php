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

namespace SP\Auth;

use SP\Auth\Browser\Browser;
use SP\Auth\Browser\BrowserAuthData;
use SP\Auth\Database\Database;
use SP\Auth\Database\DatabaseAuthData;
use SP\Auth\Ldap\LdapAuthData;
use SP\Auth\Ldap\LdapMsAds;
use SP\Auth\Ldap\LdapStd;
use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\DataModel\UserLoginData;
use SP\Util\Checks;

defined('APP_ROOT') || die();

/**
 * Class Auth
 *
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 *
 * @package SP\Auth
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
    protected $UserData;

    /**
     * Auth constructor.
     *
     * @param UserLoginData $UserData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct(UserLoginData $UserData)
    {
        $this->UserData = $UserData;

        $this->registerAuth('authBrowser');

        if (Checks::ldapIsAvailable() && Checks::ldapIsEnabled()) {
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
            throw new SPException(SPException::SP_ERROR, __('Método ya inicializado', false), __FUNCTION__);
        } elseif (!method_exists($this, $auth)) {
            throw new SPException(SPException::SP_ERROR, __('Método no disponible', false), __FUNCTION__);
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

        foreach ($this->auths as $pAuth) {
            $pResult = call_user_func([$this, $pAuth]);

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
        $Ldap = Config::getConfig()->isLdapAds() ? new LdapMsAds() : new LdapStd();

        $LdapAuthData = $Ldap->getLdapAuthData();

        if (!$Ldap->authenticate($this->UserData)) {
            return $LdapAuthData->getAuthenticated() === 1 ? $LdapAuthData : false;
        }

        // Comprobamos si la cuenta está bloqueada o expirada
        if ($LdapAuthData->getExpire() > 0) {
            $LdapAuthData->setStatusCode(701);
        } elseif (!$LdapAuthData->isInGroup()) {
            $LdapAuthData->setStatusCode(702);
        }

        $LdapAuthData->setAuthenticated(1);

        return $LdapAuthData;
    }

    /**
     * Autentificación de usuarios con base de datos
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @return DatabaseAuthData
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function authDatabase()
    {
        $AuthDatabase = new Database();
        return $AuthDatabase->authenticate($this->UserData);
    }

    /**
     * Autentificación de usuario con credenciales del navegador
     *
     * @return BrowserAuthData
     */
    public function authBrowser()
    {
        $AuthBrowser = new Browser();
        return $AuthBrowser->authenticate($this->UserData);
    }
}
