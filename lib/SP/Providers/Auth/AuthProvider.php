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

namespace SP\Providers\Auth;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigData;
use SP\DataModel\UserLoginData;
use SP\Providers\Auth\Browser\Browser;
use SP\Providers\Auth\Browser\BrowserAuthData;
use SP\Providers\Auth\Database\Database;
use SP\Providers\Auth\Database\DatabaseAuthData;
use SP\Providers\Auth\Ldap\Ldap;
use SP\Providers\Auth\Ldap\LdapAuth;
use SP\Providers\Auth\Ldap\LdapAuthData;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Provider;
use SP\Services\Auth\AuthException;

defined('APP_ROOT') || die();

/**
 * Class Auth
 *
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 *
 * @package SP\Providers\Auth
 */
final class AuthProvider extends Provider
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
     * @var Browser
     */
    protected $browser;
    /**
     * @var Database
     */
    protected $database;

    /**
     * Probar los métodos de autentificación
     *
     * @param UserLoginData $userLoginData
     *
     * @return false|AuthResult[]
     * @uses authDatabase
     * @uses authBrowser
     *
     * @uses authLdap
     */
    public function doAuth(UserLoginData $userLoginData)
    {
        $this->userLoginData = $userLoginData;

        $auths = [];

        foreach ($this->auths as $authType) {
            /** @var AuthDataBase $data */
            $data = $this->{$authType}();

            if ($data !== false) {
                $auths[] = new AuthResult($authType, $data);
            }
        }

        return count($auths) > 0 ? $auths : false;
    }

    /**
     * Autentificación de usuarios con LDAP.
     *
     * @return bool|LdapAuthData
     * @throws AuthException
     * @throws LdapException
     */
    public function authLdap()
    {
        $ldap = $this->getLdapAuth();
        $ldapAuthData = $ldap->getLdapAuthData();

        $ldapAuthData->setAuthenticated($ldap->authenticate($this->userLoginData));

        if ($ldapAuthData->getAuthenticated()) {
            // Comprobamos si la cuenta está bloqueada o expirada
            if ($ldapAuthData->getExpire() > 0) {
                $ldapAuthData->setStatusCode(LdapAuth::ACCOUNT_EXPIRED);
            } elseif (!$ldapAuthData->isInGroup()) {
                $ldapAuthData->setStatusCode(LdapAuth::ACCOUNT_NO_GROUPS);
            }
        }

        return $ldapAuthData;
    }

    /**
     * @return LdapAuth
     * @throws LdapException
     */
    private function getLdapAuth()
    {
        $data = LdapParams::getServerAndPort($this->configData->getLdapServer());

        $ldapParams = (new LdapParams())
            ->setServer($data['server'])
            ->setPort(isset($data['port']) ? $data['port'] : 389)
            ->setSearchBase($this->configData->getLdapBase())
            ->setGroup($this->configData->getLdapGroup())
            ->setBindDn($this->configData->getLdapBindUser())
            ->setBindPass($this->configData->getLdapBindPass())
            ->setType($this->configData->getLdapType());

        return new LdapAuth(
            Ldap::factory(
                $ldapParams,
                $this->eventDispatcher,
                $this->configData->isDebug()),
            $this->eventDispatcher
        );
    }

    /**
     * Autentificación de usuarios con base de datos
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @return DatabaseAuthData
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function authDatabase()
    {
        return $this->database->authenticate($this->userLoginData);
    }

    /**
     * Autentificación de usuario con credenciales del navegador
     *
     * @return BrowserAuthData
     */
    public function authBrowser()
    {
        return $this->browser->authenticate($this->userLoginData);
    }

    /**
     * Auth constructor.
     *
     * @param Container $dic
     *
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function initialize(Container $dic)
    {
        $this->configData = $this->config->getConfigData();

        if ($this->configData->isAuthBasicEnabled()) {
            $this->registerAuth('authBrowser');
            $this->browser = $dic->get(Browser::class);
        }

        if ($this->configData->isLdapEnabled()) {
            $this->registerAuth('authLdap');
        }

        $this->registerAuth('authDatabase');
        $this->database = $dic->get(Database::class);
    }

    /**
     * Registrar un método de autentificación primarios
     *
     * @param string $auth Función de autentificación
     *
     * @throws AuthException
     */
    protected function registerAuth($auth)
    {
        if (!method_exists($this, $auth)) {
            throw new AuthException(__u('Method unavailable'), AuthException::ERROR, __FUNCTION__);
        }

        if (array_key_exists($auth, $this->auths)) {
            throw new AuthException(__u('Method already initialized'), AuthException::ERROR, __FUNCTION__);
        }

        $this->auths[$auth] = $auth;
    }
}
