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

use SP\DataModel\UserPreferencesData;

/**
 * Class UserLoginResponse
 *
 * @package SP\Services\User
 */
final class UserLoginResponse
{
    private ?int $id = null;
    private ?string $login = null;
    private ?string $ssoLogin = null;
    private ?string $name = null;
    private ?string $email = null;
    private int $userGroupId = 0;
    private ?string $userGroupName = null;
    private int $userProfileId = 0;
    private bool $isAdminApp = false;
    private bool $isAdminAcc = false;
    private bool $isDisabled = false;
    private bool $isChangePass = false;
    private bool $isChangedPass = false;
    private bool $isLdap = false;
    private bool $isMigrate = false;
    private ?UserPreferencesData $preferences = null;
    private ?string $pass = null;
    private ?string $hashSalt = null;
    private ?string $mPass = null;
    private ?string $mKey = null;
    private int $lastUpdateMPass = 0;
    private ?int $lastUpdate = null;

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): UserLoginResponse
    {
        $this->login = $login;
        return $this;
    }

    public function getSsoLogin(): ?string
    {
        return $this->ssoLogin;
    }

    public function setSsoLogin(?string $ssoLogin): UserLoginResponse
    {
        $this->ssoLogin = $ssoLogin;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): UserLoginResponse
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): UserLoginResponse
    {
        $this->email = $email;
        return $this;
    }

    public function getUserGroupId(): int
    {
        return $this->userGroupId;
    }

    public function setUserGroupId(int $userGroupId): UserLoginResponse
    {
        $this->userGroupId = $userGroupId;
        return $this;
    }

    public function getUserProfileId(): int
    {
        return $this->userProfileId;
    }

    public function setUserProfileId(int $userProfileId): UserLoginResponse
    {
        $this->userProfileId = $userProfileId;
        return $this;
    }

    public function getIsAdminApp(): bool
    {
        return $this->isAdminApp;
    }

    public function setIsAdminApp(bool $isAdminApp): UserLoginResponse
    {
        $this->isAdminApp = $isAdminApp;
        return $this;
    }

    public function getIsAdminAcc(): bool
    {
        return $this->isAdminAcc;
    }

    public function setIsAdminAcc(bool $isAdminAcc): UserLoginResponse
    {
        $this->isAdminAcc = $isAdminAcc;
        return $this;
    }

    public function getIsDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function setIsDisabled(bool $isDisabled): UserLoginResponse
    {
        $this->isDisabled = $isDisabled;
        return $this;
    }

    public function getIsChangePass(): bool
    {
        return $this->isChangePass;
    }

    public function setIsChangePass(bool $isChangePass): UserLoginResponse
    {
        $this->isChangePass = $isChangePass;
        return $this;
    }

    public function getIsChangedPass(): bool
    {
        return $this->isChangedPass;
    }

    public function setIsChangedPass(bool $isChangedPass): UserLoginResponse
    {
        $this->isChangedPass = $isChangedPass;
        return $this;
    }

    public function getIsLdap(): bool
    {
        return $this->isLdap;
    }

    public function setIsLdap(bool $isLdap): UserLoginResponse
    {
        $this->isLdap = $isLdap;
        return $this;
    }

    public function getIsMigrate(): bool
    {
        return $this->isMigrate;
    }

    public function setIsMigrate(bool $isMigrate): UserLoginResponse
    {
        $this->isMigrate = $isMigrate;
        return $this;
    }

    public function getPreferences(): ?UserPreferencesData
    {
        return $this->preferences;
    }

    public function setPreferences(UserPreferencesData $preferences): UserLoginResponse
    {
        $this->preferences = $preferences;
        return $this;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(string $pass): UserLoginResponse
    {
        $this->pass = $pass;
        return $this;
    }

    public function getMPass(): ?string
    {
        return $this->mPass;
    }

    public function setMPass(string $mPass): UserLoginResponse
    {
        $this->mPass = $mPass;
        return $this;
    }

    public function getMKey(): ?string
    {
        return $this->mKey;
    }

    public function setMKey(string $mKey): UserLoginResponse
    {
        $this->mKey = $mKey;
        return $this;
    }

    public function getLastUpdateMPass(): int
    {
        return $this->lastUpdateMPass;
    }

    public function setLastUpdateMPass(int $lastUpdateMPass): UserLoginResponse
    {
        $this->lastUpdateMPass = $lastUpdateMPass;
        return $this;
    }

    public function getHashSalt(): ?string
    {
        return $this->hashSalt;
    }

    public function setHashSalt(string $hashSalt): UserLoginResponse
    {
        $this->hashSalt = $hashSalt;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): UserLoginResponse
    {
        $this->id = $id;
        return $this;
    }

    public function getUserGroupName(): ?string
    {
        return $this->userGroupName;
    }

    public function setUserGroupName(string $userGroupName): UserLoginResponse
    {
        $this->userGroupName = $userGroupName;
        return $this;
    }


    public function getLastUpdate(): int
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(int $lastUpdate): UserLoginResponse
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }
}