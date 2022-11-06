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

namespace SP\DataModel;

use SP\Domain\Account\Out\AccountData;

/**
 * Class AccountExtData
 *
 * @package SP\DataModel
 */
class AccountExtData extends AccountData
{
    protected array   $usersId        = [];
    protected array   $userGroupsId   = [];
    protected array   $tags           = [];
    protected ?string $categoryName   = null;
    protected ?string $clientName     = null;
    protected ?string $userGroupName  = null;
    protected ?string $userName       = null;
    protected ?string $userLogin      = null;
    protected ?string $userEditName   = null;
    protected ?string $userEditLogin  = null;
    protected ?string $publicLinkHash = null;

    public function getUserEditName(): ?string
    {
        return $this->userEditName;
    }

    public function getUserEditLogin(): ?string
    {
        return $this->userEditLogin;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function getUserGroupName(): ?string
    {
        return $this->userGroupName;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getUserLogin(): ?string
    {
        return $this->userLogin;
    }
}