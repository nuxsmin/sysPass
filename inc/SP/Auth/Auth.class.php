<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Auth;

use SP\Auth\Database\Database;
use SP\Auth\Database\DatabaseAuthData;
use SP\Auth\Ldap\LdapMsAds;
use SP\Auth\Ldap\LdapStd;
use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Core\Plugin\PluginAwareBase;
use SP\DataModel\UserData;
use SP\Util\Checks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 */
class Auth extends PluginAwareBase
{
    public static $status = 0;
    public static $description;
    /**
     * @var array
     */
    protected $authMethods = [];
    /**
     * @var UserData
     */
    protected $UserData;

    /**
     * Auth constructor.
     * @param UserData $UserData
     */
    public function __construct(UserData $UserData)
    {
        $this->registerAuth('ldap', 'authUserLDAP');
        $this->registerAuth('mysql', 'authUserMySQL');
    }

    /**
     * Registrar un método de autentificación
     *
     * @param string $type Tipo de autentificación
     * @param string $auth Función de autentificación
     * @throws SPException
     */
    protected function registerAuth($type, $auth)
    {
        if (array_key_exists($type, $this->authMethods)) {
            throw new SPException(SPException::SP_ERROR, _('Método ya inicializado'), __FUNCTION__);
        } elseif (!method_exists($this, $auth)) {
            throw new SPException(SPException::SP_ERROR, _('Método no disponible'), __FUNCTION__);
        }

        $this->authMethods[$type] = $auth;
    }


    /**
     * Probar los métodos de autentificación
     *
     * @return mixed
     */
    public function doAuth()
    {
        foreach ($this->authMethods as $type => $auth) {
            $result = call_user_func([$this, $auth]);

            if ($result !== false) {
                return ['type' => $type, 'data' => $result];
            }
        }

        return false;
    }

    /**
     * Autentificación de usuarios con LDAP.
     *
     * @return bool|Ldap\LdapAuthDataBase
     */
    public function authUserLDAP()
    {
        if (!Checks::ldapIsAvailable()
            || !Checks::ldapIsEnabled()
        ) {
            return false;
        }

        $Ldap = Config::getConfig()->isLdapAds() ? new LdapMsAds() : new LdapStd();

        if (!$Ldap->authenticate($this->UserData)) {
            return false;
        }

        $LdapAuthData = $Ldap->getLdapAuthData();

        // Comprobamos si la cuenta está bloqueada o expirada
        if ($LdapAuthData->getExpire() > 0) {
            $LdapAuthData->setStatus(701);
        } elseif (!$LdapAuthData->isInGroup()) {
            $LdapAuthData->setStatus(702);
        }

        return $LdapAuthData;
    }

    /**
     * Autentificación de usuarios con MySQL.
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado desde phpPMS,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @return DatabaseAuthData
     */
    public function authUserMySQL()
    {
        $AuthDatabase = new Database();

        return $AuthDatabase->authenticate($this->UserData);
    }

    /**
     * Devuelve los métodos de autentificación
     *
     * @return array
     */
    public function getAuthMethods()
    {
        return $this->authMethods;
    }
}
