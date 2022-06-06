<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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


use SP\DataModel\UserLoginData;
use SP\Domain\Auth\Services\AuthException;
use SP\Providers\Auth\Browser\BrowserAuthInterface;
use SP\Providers\Auth\Ldap\LdapAuthInterface;

/**
 * Class Auth
 *
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 *
 * @package SP\Providers\Auth
 */
interface AuthProviderInterface
{
    /**
     * Probar los métodos de autentificación
     *
     * @param  UserLoginData  $userLoginData
     *
     * @return false|AuthResult[]
     */
    public function doAuth(UserLoginData $userLoginData);

    /**
     * Auth initializer
     *
     * @throws AuthException
     */
    public function initialize(): void;

    public function withLdapAuth(LdapAuthInterface $ldapAuth): void;

    public function withBrowserAuth(BrowserAuthInterface $browserAuth): void;
}