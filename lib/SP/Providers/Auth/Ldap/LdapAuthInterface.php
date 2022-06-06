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

namespace SP\Providers\Auth\Ldap;


use SP\Providers\Auth\AuthInterface;

/**
 * Class LdapBase
 *
 * @package Auth\Ldap
 */
interface LdapAuthInterface extends AuthInterface
{
    public const ACCOUNT_NO_GROUPS = 702;
    public const ACCOUNT_EXPIRED   = 701;

    /**
     * @return LdapAuthData
     */
    public function getLdapAuthData(): LdapAuthData;

    /**
     * @return string
     */
    public function getUserLogin(): ?string;

    /**
     * @param  string  $userLogin
     */
    public function setUserLogin(string $userLogin): void;

    /**
     * Obtener los atributos del usuario.
     *
     * @param  string  $userLogin
     *
     * @return LdapAuthData con los atributos disponibles y sus valores
     * @throws LdapException
     */
    public function getAttributes(string $userLogin): LdapAuthData;
}