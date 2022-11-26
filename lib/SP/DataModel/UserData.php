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

use SP\Domain\Common\Adapters\DataModelInterface;

defined('APP_ROOT') || die();

/**
 * Class UserBasicData
 *
 * @package SP\DataModel
 */
class UserData extends UserPassData implements DataModelInterface
{
    public ?string $login         = null;
    public ?string $ssoLogin      = null;
    public ?string $name          = null;
    public ?string $email         = null;
    public ?string $notes         = null;
    public ?int    $userGroupId   = null;
    public ?int    $userProfileId = null;
    public ?int    $isAdminApp    = null;
    public bool    $isAdminAcc    = false;
    public bool    $isDisabled    = false;
    public bool    $isChangePass  = false;
    public bool    $isChangedPass = false;
    public bool    $isLdap        = false;
    public ?int    $loginCount    = null;
    public ?string $lastLogin     = null;
    public ?string $lastUpdate    = null;
    public ?bool   $isMigrate     = false;
    public ?string $preferences   = null;
    public ?string $userGroupName = null;

    public function getLoginCount(): int
    {
        return (int)$this->loginCount;
    }

    public function getLastLogin(): ?string
    {
        return $this->lastLogin;
    }

    /**
     * @return string
     */
    public function getLastUpdate()
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

    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(string $notes)
    {
        $this->notes = $notes;
    }

    public function getUserGroupId(): int
    {
        return (int)$this->userGroupId;
    }

    public function setUserGroupId(int $userGroupId)
    {
        $this->userGroupId = (int)$userGroupId;
    }

    public function getUserProfileId(): int
    {
        return (int)$this->userProfileId;
    }

    public function setUserProfileId(int $userProfileId)
    {
        $this->userProfileId = (int)$userProfileId;
    }

    public function isAdminApp(): int
    {
        return (int)$this->isAdminApp;
    }

    public function setIsAdminApp(bool $isAdminApp)
    {
        $this->isAdminApp = $isAdminApp;
    }

    public function isAdminAcc(): int
    {
        return (int)$this->isAdminAcc;
    }

    public function setIsAdminAcc(bool $isAdminAcc)
    {
        $this->isAdminAcc = $isAdminAcc;
    }

    public function isDisabled(): int
    {
        return (int)$this->isDisabled;
    }

    public function setIsDisabled(bool $isDisabled)
    {
        $this->isDisabled = $isDisabled;
    }

    public function isChangePass(): int
    {
        return (int)$this->isChangePass;
    }

    public function setIsChangePass(bool $isChangePass)
    {
        $this->isChangePass = $isChangePass;
    }

    public function isLdap(): int
    {
        return (int)$this->isLdap;
    }

    public function setIsLdap(bool $isLdap)
    {
        $this->isLdap = $isLdap;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login)
    {
        $this->login = $login;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
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

    public function setSsoLogin(string $ssoLogin)
    {
        $this->ssoLogin = $ssoLogin;
    }
}
