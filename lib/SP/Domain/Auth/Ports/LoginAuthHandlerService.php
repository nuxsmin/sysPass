<?php
declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Auth\Ports;

use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Providers\Browser\BrowserAuthData;
use SP\Domain\Auth\Providers\Database\DatabaseAuthData;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Providers\Ldap\LdapAuthData;

/**
 * Class LoginDatabase
 */
interface LoginAuthHandlerService
{
    /**
     * Check response from database authentication
     *
     * @param DatabaseAuthData $authData
     * @param UserLoginDto $userLoginDto
     * @throws AuthException
     */
    public function authDatabase(DatabaseAuthData $authData, UserLoginDto $userLoginDto): void;

    /**
     * Check response from browser authentication
     *
     * @param BrowserAuthData $authData
     * @param UserLoginDto $userLoginDto
     * @throws AuthException
     */
    public function authBrowser(BrowserAuthData $authData, UserLoginDto $userLoginDto): void;

    /**
     * Check response from LDAP authentication
     *
     * @param LdapAuthData $authData
     * @param UserLoginDto $userLoginDto
     * @throws AuthException
     */
    public function authLdap(LdapAuthData $authData, UserLoginDto $userLoginDto): void;
}
