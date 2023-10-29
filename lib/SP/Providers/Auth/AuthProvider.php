<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Application;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserLoginData;
use SP\Domain\Auth\Ports\LdapAuthInterface;
use SP\Domain\Auth\Services\AuthException;
use SP\Providers\Auth\Browser\BrowserAuthInterface;
use SP\Providers\Auth\Database\DatabaseAuthInterface;
use SP\Providers\Provider;

use function SP\__u;

defined('APP_ROOT') || die();

/**
 * Class Auth
 *
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 *
 * @package SP\Providers\Auth
 */
class AuthProvider extends Provider implements AuthProviderInterface
{
    /**
     * @var callable[]
     */
    protected array                 $auths       = [];
    protected ?BrowserAuthInterface $browserAuth = null;
    protected ?LdapAuthInterface    $ldapAuth    = null;

    public function __construct(
        Application                              $application,
        protected readonly DatabaseAuthInterface $databaseAuth
    ) {
        parent::__construct($application);
    }

    /**
     * Probar los métodos de autentificación
     *
     * @param UserLoginData $userLoginData
     *
     * @return false|AuthResult[]
     */
    public function doAuth(UserLoginData $userLoginData): array|bool
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
     * Auth initializer
     *
     * @throws AuthException
     */
    public function initialize(): void
    {
        $configData = $this->config->getConfigData();

        if ($this->browserAuth && $configData->isAuthBasicEnabled()) {
            $this->registerAuth(
                function (UserLoginData $userLoginData) {
                    return $this->browserAuth->authenticate($userLoginData);
                },
                'authBrowser'
            );
        }

        if ($this->ldapAuth && $configData->isLdapEnabled()) {
            $this->registerAuth(
                function (UserLoginData $userLoginData) {
                    $ldapAuthData = $this->ldapAuth->authenticate($userLoginData);

                    if ($ldapAuthData->getAuthenticated()) {
                        // Comprobamos si la cuenta está bloqueada o expirada
                        if ($ldapAuthData->getExpire() > 0) {
                            $ldapAuthData->setStatusCode(LdapAuthInterface::ACCOUNT_EXPIRED);
                        } elseif (!$ldapAuthData->isInGroup()) {
                            $ldapAuthData->setStatusCode(LdapAuthInterface::ACCOUNT_NO_GROUPS);
                        }
                    }

                    return $ldapAuthData;
                },
                'authLdap'
            );
        }

        $this->registerAuth(
            function (UserLoginData $userLoginData) {
                return $this->databaseAuth->authenticate($userLoginData);
            },
            'authDatabase'
        );
    }

    /**
     * Registrar un método de autentificación primarios
     *
     * @param callable $auth Función de autentificación
     * @param string $name
     *
     * @throws AuthException
     */
    private function registerAuth(callable $auth, string $name): void
    {
        if (array_key_exists($name, $this->auths)) {
            throw new AuthException(
                __u('Authentication already initialized'),
                SPException::ERROR,
                __FUNCTION__
            );
        }

        $this->auths[$name] = $auth;
    }

    public function withLdapAuth(LdapAuthInterface $ldapAuth): void
    {
        $this->ldapAuth = $ldapAuth;
    }

    public function withBrowserAuth(BrowserAuthInterface $browserAuth): void
    {
        $this->browserAuth = $browserAuth;
    }
}
