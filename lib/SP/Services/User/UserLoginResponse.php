<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
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
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $login;
    /**
     * @var string
     */
    private $ssoLogin;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $email;
    /**
     * @var int
     */
    private $userGroupId = 0;
    /**
     * @var string
     */
    private $userGroupName;
    /**
     * @var int
     */
    private $userProfileId = 0;
    /**
     * @var string
     */
    private $userProfileName;
    /**
     * @var int
     */
    private $isAdminApp = 0;
    /**
     * @var int
     */
    private $isAdminAcc = 0;
    /**
     * @var int
     */
    private $isDisabled = 0;
    /**
     * @var int
     */
    private $isChangePass = 0;
    /**
     * @var int
     */
    private $isChangedPass = 0;
    /**
     * @var int
     */
    private $isLdap = 0;
    /**
     * @var int
     */
    private $isMigrate = 0;
    /**
     * @var UserPreferencesData
     */
    private $preferences;
    /**
     * @var string
     */
    private $pass;
    /**
     * @var string
     */
    private $hashSalt;
    /**
     * @var string
     */
    private $mPass;
    /**
     * @var string
     */
    private $mKey;
    /**
     * @var int
     */
    private $lastUpdateMPass = 0;
    /**
     * @var int
     */
    private $lastUpdate;

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     *
     * @return UserLoginResponse
     */
    public function setLogin($login)
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getSsoLogin()
    {
        return $this->ssoLogin;
    }

    /**
     * @param string $ssoLogin
     *
     * @return UserLoginResponse
     */
    public function setSsoLogin($ssoLogin)
    {
        $this->ssoLogin = $ssoLogin;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return UserLoginResponse
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return UserLoginResponse
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->userGroupId;
    }

    /**
     * @param int $userGroupId
     *
     * @return UserLoginResponse
     */
    public function setUserGroupId($userGroupId)
    {
        $this->userGroupId = (int)$userGroupId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserProfileId()
    {
        return $this->userProfileId;
    }

    /**
     * @param int $userProfileId
     *
     * @return UserLoginResponse
     */
    public function setUserProfileId($userProfileId)
    {
        $this->userProfileId = (int)$userProfileId;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsAdminApp()
    {
        return $this->isAdminApp;
    }

    /**
     * @param int $isAdminApp
     *
     * @return UserLoginResponse
     */
    public function setIsAdminApp($isAdminApp)
    {
        $this->isAdminApp = (int)$isAdminApp;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsAdminAcc()
    {
        return $this->isAdminAcc;
    }

    /**
     * @param int $isAdminAcc
     *
     * @return UserLoginResponse
     */
    public function setIsAdminAcc($isAdminAcc)
    {
        $this->isAdminAcc = (int)$isAdminAcc;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * @param int $isDisabled
     *
     * @return UserLoginResponse
     */
    public function setIsDisabled($isDisabled)
    {
        $this->isDisabled = (int)$isDisabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsChangePass()
    {
        return $this->isChangePass;
    }

    /**
     * @param int $isChangePass
     *
     * @return UserLoginResponse
     */
    public function setIsChangePass($isChangePass)
    {
        $this->isChangePass = (int)$isChangePass;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsChangedPass()
    {
        return $this->isChangedPass;
    }

    /**
     * @param int $isChangedPass
     *
     * @return UserLoginResponse
     */
    public function setIsChangedPass($isChangedPass)
    {
        $this->isChangedPass = (int)$isChangedPass;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsLdap()
    {
        return $this->isLdap;
    }

    /**
     * @param int $isLdap
     *
     * @return UserLoginResponse
     */
    public function setIsLdap($isLdap)
    {
        $this->isLdap = (int)$isLdap;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsMigrate()
    {
        return $this->isMigrate;
    }

    /**
     * @param int $isMigrate
     *
     * @return UserLoginResponse
     */
    public function setIsMigrate($isMigrate)
    {
        $this->isMigrate = (int)$isMigrate;
        return $this;
    }

    /**
     * @return UserPreferencesData
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @param mixed $preferences
     *
     * @return UserLoginResponse
     */
    public function setPreferences(UserPreferencesData $preferences)
    {
        $this->preferences = $preferences;
        return $this;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     *
     * @return UserLoginResponse
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
        return $this;
    }

    /**
     * @return string
     */
    public function getMPass()
    {
        return $this->mPass;
    }

    /**
     * @param string $mPass
     *
     * @return UserLoginResponse
     */
    public function setMPass($mPass)
    {
        $this->mPass = $mPass;
        return $this;
    }

    /**
     * @return string
     */
    public function getMKey()
    {
        return $this->mKey;
    }

    /**
     * @param string $mKey
     *
     * @return UserLoginResponse
     */
    public function setMKey($mKey)
    {
        $this->mKey = $mKey;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastUpdateMPass()
    {
        return $this->lastUpdateMPass;
    }

    /**
     * @param int $lastUpdateMPass
     *
     * @return UserLoginResponse
     */
    public function setLastUpdateMPass($lastUpdateMPass)
    {
        $this->lastUpdateMPass = (int)$lastUpdateMPass;
        return $this;
    }

    /**
     * @return string
     */
    public function getHashSalt()
    {
        return $this->hashSalt;
    }

    /**
     * @param string $hashSalt
     *
     * @return UserLoginResponse
     */
    public function setHashSalt($hashSalt)
    {
        $this->hashSalt = $hashSalt;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return UserLoginResponse
     */
    public function setId($id)
    {
        $this->id = (int)$id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserGroupName()
    {
        return $this->userGroupName;
    }

    /**
     * @param string $userGroupName
     *
     * @return UserLoginResponse
     */
    public function setUserGroupName($userGroupName)
    {
        $this->userGroupName = $userGroupName;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserProfileName()
    {
        return $this->userProfileName;
    }

    /**
     * @param string $userProfileName
     *
     * @return UserLoginResponse
     */
    public function setUserProfileName($userProfileName)
    {
        $this->userProfileName = $userProfileName;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastUpdate(): int
    {
        return $this->lastUpdate;
    }

    /**
     * @param int $lastUpdate
     *
     * @return UserLoginResponse
     */
    public function setLastUpdate(int $lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }
}