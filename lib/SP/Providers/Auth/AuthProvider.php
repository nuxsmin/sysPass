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

namespace SP\Providers\Auth;

use Psr\Container\ContainerInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\UserLoginData;
use SP\Providers\Auth\Browser\Browser;
use SP\Providers\Auth\Database\Database;
use SP\Providers\Auth\Ldap\Ldap;
use SP\Providers\Auth\Ldap\LdapAuth;
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
     * @var callable[]
     */
    protected array $auths = [];

    /**
     * Probar los métodos de autentificación
     *
     * @param UserLoginData $userLoginData
     *
     * @return false|AuthResult[]
     */
    public function doAuth(UserLoginData $userLoginData)
    {
        $authsResult = [];

        foreach ($this->auths as $authName => $auth) {
            $data = $auth($userLoginData);

            if ($data instanceof AuthDataBase) {
                $authsResult[] = new AuthResult($authName, $data);
            }
        }

        return count($authsResult) > 0 ? $authsResult : false;
    }

    /**
     * Auth constructor.
     *
     * @param ContainerInterface $dic
     *
     * @throws AuthException
     */
    protected function initialize(ContainerInterface $dic): void
    {
        $configData = $this->config->getConfigData();

        if ($configData->isAuthBasicEnabled()) {
            $this->registerAuth(
                function (UserLoginData $userLoginData) use ($dic) {
                    $dic->get(Browser::class)
                        ->authenticate($userLoginData);
                },
                'authBrowser');
        }

        if ($configData->isLdapEnabled()) {
            $this->registerAuth(
                function (UserLoginData $userLoginData) use ($configData) {
                    $data = LdapParams::getServerAndPort($configData->getLdapServer());

                    if (count($data) === 0) {
                        throw new ValidationException(__u('Wrong LDAP parameters'));
                    }

                    $ldapParams = new LdapParams();
                    $ldapParams->setServer($data['server']);
                    $ldapParams->setPort($data['port'] ?? 389);
                    $ldapParams->setSearchBase($configData->getLdapBase());
                    $ldapParams->setGroup($configData->getLdapGroup());
                    $ldapParams->setBindDn($configData->getLdapBindUser());
                    $ldapParams->setBindPass($configData->getLdapBindPass());
                    $ldapParams->setType($configData->getLdapType());
                    $ldapParams->setFilterUserObject($configData->getLdapFilterUserObject());
                    $ldapParams->setFilterGroupObject($configData->getLdapFilterGroupObject());
                    $ldapParams->setFilterUserAttributes($configData->getLdapFilterUserAttributes());
                    $ldapParams->setFilterGroupAttributes($configData->getLdapFilterGroupAttributes());

                    $ldapAuth = new LdapAuth(
                        Ldap::factory(
                            $ldapParams,
                            $this->eventDispatcher,
                            $configData->isDebug()),
                        $this->eventDispatcher,
                        $configData
                    );

                    $ldapAuthData = $ldapAuth->getLdapAuthData();

                    $ldapAuthData->setAuthenticated($ldapAuth->authenticate($userLoginData));

                    if ($ldapAuthData->getAuthenticated()) {
                        // Comprobamos si la cuenta está bloqueada o expirada
                        if ($ldapAuthData->getExpire() > 0) {
                            $ldapAuthData->setStatusCode(LdapAuth::ACCOUNT_EXPIRED);
                        } elseif (!$ldapAuthData->isInGroup()) {
                            $ldapAuthData->setStatusCode(LdapAuth::ACCOUNT_NO_GROUPS);
                        }
                    }

                    return $ldapAuthData;
                },
                'authLdap');
        }

        $this->registerAuth(
            function (UserLoginData $userLoginData) use ($dic) {
                return $dic->get(Database::class)
                    ->authenticate($userLoginData);
            },
            'authDatabase'
        );
    }

    /**
     * Registrar un método de autentificación primarios
     *
     * @param callable $auth Función de autentificación
     * @param string   $name
     *
     * @throws AuthException
     */
    private function registerAuth(callable $auth, string $name): void
    {
        if (array_key_exists($name, $this->auths)) {
            throw new AuthException(__u('Authentication already initialized'),
                SPException::ERROR,
                __FUNCTION__);
        }

        $this->auths[$name] = $auth;
    }
}
