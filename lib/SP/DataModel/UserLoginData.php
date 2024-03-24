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

namespace SP\DataModel;

use SP\Domain\User\Services\UserLoginResponse;

/**
 * Class UserLoginData
 *
 * @package SP\DataModel
 */
class UserLoginData
{
    protected ?string            $loginUser         = null;
    protected ?string            $loginPass         = null;
    protected ?UserLoginResponse $userLoginResponse = null;

    public function getLoginUser(): ?string
    {
        return $this->loginUser;
    }

    public function setLoginUser(string $login): void
    {
        $this->loginUser = $login;
    }

    public function getLoginPass(): ?string
    {
        return $this->loginPass;
    }

    public function setLoginPass(string $loginPass): void
    {
        $this->loginPass = $loginPass;
    }

    public function getUserLoginResponse(): ?UserLoginResponse
    {
        return $this->userLoginResponse;
    }

    public function setUserLoginResponse(UserLoginResponse $userLoginResponse = null): void
    {
        $this->userLoginResponse = $userLoginResponse;
    }
}
