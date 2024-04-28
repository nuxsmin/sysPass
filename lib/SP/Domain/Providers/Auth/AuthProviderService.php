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

namespace SP\Domain\Providers\Auth;

use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\User\Dtos\UserDataDto;

/**
 * Interface AuthProviderService
 */
interface AuthProviderService
{
    /**
     * Authenticate using the registered authentication providers.
     *
     * It iterates over the registered authentication providers and returns whenever an authoritative provider
     * successfully authenticates the user.
     *
     * @param UserLoginDto $userLoginData
     * @param callable(AuthResult):void $callback A callback function to call after the authentication.
     * @return UserDataDto|null
     */
    public function doAuth(UserLoginDto $userLoginData, callable $callback): ?UserDataDto;
}
