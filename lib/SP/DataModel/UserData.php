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

namespace SP\DataModel;

defined('APP_ROOT') || die();

/**
 * Class UserBasicData
 *
 * @package SP\DataModel
 */
class UserData extends UserPassData implements DataModelInterface
{
    /**
     * @var string
     */
    public $login;
    /**
     * @var string
     */
    public $ssoLogin;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $notes;
    /**
     * @var int
     */
    public $userGroupId = 0;
    /**
     * @var int
     */
    public $userProfileId = 0;
    /**
     * @var bool
     */
    public $isAdminApp = 0;
    /**
     * @var bool
     */
    public $isAdminAcc = 0;
    /**
     * @var bool
     */
    public $isDisabled = 0;
    /**
     * @var bool
     */
    public $isChangePass = 0;
    /**
     * @var bool
     */
    public $isChangedPass = 0;
    /**
     * @var bool
     */
    public $isLdap = 0;
    /**
     * @var int
     */
    public $loginCount = 0;
    /**
     * @var string
     */
    public $lastLogin;
    /**
     * @var string
     */
    public $lastUpdate;
    /**
     * @var bool
     */
    public $isMigrate = 0;
    /**
     * @var string
     */
    public $preferences;
    /**
     * @var string
     */
    public $userGroupName;

    /**
     * @return int
     */
    public function getLoginCount()
    {
        return (int)$this->loginCount;
    }

    /**
     * @param int $loginCount
     */
    public function setLoginCount($loginCount)
    {
        $this->loginCount = (int)$loginCount;
    }

    /**
     * @return string
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param string $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return string
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param string $lastUpdate
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * @return int
     */
    public function isMigrate()
    {
        return (int)$this->isMigrate;
    }

    /**
     * @param boolean $isMigrate
     */
    public function setIsMigrate($isMigrate)
    {
        $this->isMigrate = (int)$isMigrate;
    }

    /**
     * @return string
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @param string $preferences
     */
    public function setPreferences($preferences)
    {
        $this->preferences = $preferences;
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
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return (int)$this->userGroupId;
    }

    /**
     * @param int $userGroupId
     */
    public function setUserGroupId($userGroupId)
    {
        $this->userGroupId = (int)$userGroupId;
    }

    /**
     * @return int
     */
    public function getUserProfileId()
    {
        return (int)$this->userProfileId;
    }

    /**
     * @param int $userProfileId
     */
    public function setUserProfileId($userProfileId)
    {
        $this->userProfileId = (int)$userProfileId;
    }

    /**
     * @return int
     */
    public function isAdminApp()
    {
        return (int)$this->isAdminApp;
    }

    /**
     * @param boolean $isAdminApp
     */
    public function setIsAdminApp($isAdminApp)
    {
        $this->isAdminApp = (int)$isAdminApp;
    }

    /**
     * @return int
     */
    public function isAdminAcc()
    {
        return (int)$this->isAdminAcc;
    }

    /**
     * @param boolean $isAdminAcc
     */
    public function setIsAdminAcc($isAdminAcc)
    {
        $this->isAdminAcc = (int)$isAdminAcc;
    }

    /**
     * @return int
     */
    public function isDisabled()
    {
        return (int)$this->isDisabled;
    }

    /**
     * @param boolean $isDisabled
     */
    public function setIsDisabled($isDisabled)
    {
        $this->isDisabled = (int)$isDisabled;
    }

    /**
     * @return int
     */
    public function isChangePass()
    {
        return (int)$this->isChangePass;
    }

    /**
     * @param boolean $isChangePass
     */
    public function setIsChangePass($isChangePass)
    {
        $this->isChangePass = (int)$isChangePass;
    }

    /**
     * @return int
     */
    public function isLdap()
    {
        return (int)$this->isLdap;
    }

    /**
     * @param boolean $isLdap
     */
    public function setIsLdap($isLdap)
    {
        $this->isLdap = (int)$isLdap;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
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
     */
    public function setName($name)
    {
        $this->name = $name;
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
     */
    public function setUserGroupName($userGroupName)
    {
        $this->userGroupName = $userGroupName;
    }

    /**
     * @return int
     */
    public function isChangedPass()
    {
        return (int)$this->isChangedPass;
    }

    /**
     * @param int $isChangedPass
     */
    public function setIsChangedPass($isChangedPass)
    {
        $this->isChangedPass = (int)$isChangedPass;
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
     */
    public function setSsoLogin($ssoLogin)
    {
        $this->ssoLogin = $ssoLogin;
    }
}