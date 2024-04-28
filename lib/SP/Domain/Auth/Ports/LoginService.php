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

namespace SP\Domain\Auth\Ports;

use SP\Domain\Auth\Dtos\LoginResponseDto;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Providers\Auth\AuthResult;

/**
 * Interface LoginService
 */
interface LoginService
{
    /**
     * Execute login process
     *
     * @param string|null $from Set the source routable action
     * @return LoginResponseDto
     * @throws AuthException
     */
    public function doLogin(?string $from = null): LoginResponseDto;

    /**
     * Handle the authentication result to determine whether the login is successful
     *
     * @param AuthResult $authResult The authentication result
     * @throws AuthException
     * @uses LoginAuthHandlerService::authBrowser()
     * @uses LoginAuthHandlerService::authDatabase()
     * @uses LoginAuthHandlerService::authLdap()
     */
    public function handleAuthResponse(AuthResult $authResult): void;
}
