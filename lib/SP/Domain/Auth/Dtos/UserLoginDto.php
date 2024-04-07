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

namespace SP\Domain\Auth\Dtos;

use SP\Domain\User\Dtos\UserDataDto;

/**
 * Class UserLoginDto
 */
class UserLoginDto
{
    protected ?string      $loginUser   = null;
    protected ?string      $loginPass   = null;
    protected ?UserDataDto $userDataDto = null;

    /**
     * @param string|null $loginUser
     * @param string|null $loginPass
     * @param UserDataDto|null $userDataDto
     */
    public function __construct(?string $loginUser = null, ?string $loginPass = null, ?UserDataDto $userDataDto = null)
    {
        $this->loginUser = $loginUser;
        $this->loginPass = $loginPass;
        $this->userDataDto = $userDataDto;
    }

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

    public function getUserDataDto(): ?UserDataDto
    {
        return $this->userDataDto;
    }

    public function setUserDataDto(UserDataDto $userDataDto = null): void
    {
        $this->userDataDto = $userDataDto;
    }
}
