<?php
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

namespace SP\Domain\Auth\Providers;

use SP\Domain\Auth\Providers\Browser\BrowserAuthData;
use SP\Domain\Auth\Providers\Database\DatabaseAuthData;
use SP\Domain\Auth\Providers\Ldap\LdapAuthData;

/**
 * Class AuthResult
 */
final readonly class AuthResult
{

    public function __construct(
        private AuthType                                      $authTypeEnum,
        private LdapAuthData|DatabaseAuthData|BrowserAuthData $authData
    ) {
    }

    public function getAuthType(): AuthType
    {
        return $this->authTypeEnum;
    }

    public function getAuthData(): LdapAuthData|DatabaseAuthData|BrowserAuthData
    {
        return $this->authData;
    }
}
