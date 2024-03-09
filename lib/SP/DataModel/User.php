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

namespace SP\DataModel;

use SP\Domain\Common\Models\ItemWithIdAndNameModel;

/**
 * Class UserBasicData
 *
 * @package SP\DataModel
 */
class User extends UserPassData implements ItemWithIdAndNameModel
{
    protected ?string $login         = null;
    protected ?string $ssoLogin      = null;
    protected ?string $name          = null;
    protected ?string $email         = null;
    protected ?string $notes         = null;
    protected ?int    $userGroupId   = null;
    protected ?int    $userProfileId = null;
    protected ?int    $isAdminApp    = null;
    protected bool    $isAdminAcc    = false;
    protected bool    $isDisabled    = false;
    protected bool    $isChangePass  = false;
    protected bool    $isChangedPass = false;
    protected bool    $isLdap        = false;
    protected ?int    $loginCount    = null;
    protected ?string $lastLogin     = null;
    protected ?string $lastUpdate    = null;
    protected ?bool   $isMigrate     = false;
    protected ?string $preferences   = null;
    protected ?string $userGroupName = null;

    public function getLoginCount(): int
    {
        return (int)$this->loginCount;
    }

    public function getLastLogin(): ?string
    {
        return $this->lastLogin;
    }

    public function getLastUpdate(): ?string
    {
        return $this->lastUpdate;
    }

    public function isMigrate(): int
    {
        return (int)$this->isMigrate;
    }

    public function getPreferences(): ?string
    {
        return $this->preferences;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getUserGroupId(): int
    {
        return (int)$this->userGroupId;
    }

    public function getUserProfileId(): int
    {
        return (int)$this->userProfileId;
    }

    public function isAdminApp(): int
    {
        return (int)$this->isAdminApp;
    }

    public function isAdminAcc(): int
    {
        return (int)$this->isAdminAcc;
    }

    public function isDisabled(): int
    {
        return (int)$this->isDisabled;
    }

    public function isChangePass(): int
    {
        return (int)$this->isChangePass;
    }

    public function isLdap(): int
    {
        return (int)$this->isLdap;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUserGroupName(): ?string
    {
        return $this->userGroupName;
    }

    public function isChangedPass(): int
    {
        return (int)$this->isChangedPass;
    }

    public function getSsoLogin(): ?string
    {
        return $this->ssoLogin;
    }
}
