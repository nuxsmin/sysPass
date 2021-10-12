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

namespace SP\Services\User;

/**
 * Class UpdatePassRequest
 *
 * @package SP\Services\User
 */
final class UpdatePassRequest
{
    private string $pass;
    private bool $isChangePass = false;
    private bool $isChangedPass = false;

    public function __construct(string $pass)
    {
        $this->pass = $pass;
    }

    public function getPass(): string
    {
        return $this->pass;
    }

    public function getisChangePass(): bool
    {
        return $this->isChangePass;
    }

    public function setIsChangePass(bool $isChangePass): void
    {
        $this->isChangePass = $isChangePass;
    }

    public function getisChangedPass(): bool
    {
        return $this->isChangedPass;
    }

    public function setIsChangedPass(bool $isChangedPass): void
    {
        $this->isChangedPass = $isChangedPass;
    }
}