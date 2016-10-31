<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\DataModel;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
    public $user_login = '';
    /**
     * @var string
     */
    public $user_name = '';
    /**
     * @var string
     */
    public $user_email = '';
    /**
     * @var string
     */
    public $user_notes = '';
    /**
     * @var int
     */
    public $user_groupId = 0;
    /**
     * @var int
     */
    public $user_profileId = 0;
    /**
     * @var bool
     */
    public $user_isAdminApp = false;
    /**
     * @var bool
     */
    public $user_isAdminAcc = false;
    /**
     * @var bool
     */
    public $user_isDisabled = false;
    /**
     * @var bool
     */
    public $user_isChangePass = false;
    /**
     * @var bool
     */
    public $user_isLdap = false;
    /**
     * @var int
     */
    public $user_count = 0;
    /**
     * @var string
     */
    public $user_lastLogin = '';
    /**
     * @var string
     */
    public $user_lastUpdate = '';
    /**
     * @var bool
     */
    public $user_isMigrate = false;
    /**
     * @var
     */
    public $user_preferences;
    /**
     * @var string
     */
    public $usergroup_name = '';

    /**
     * @return int
     */
    public function getUserCount()
    {
        return $this->user_count;
    }

    /**
     * @param int $user_count
     */
    public function setUserCount($user_count)
    {
        $this->user_count = $user_count;
    }

    /**
     * @return string
     */
    public function getUserLastLogin()
    {
        return $this->user_lastLogin;
    }

    /**
     * @param string $user_lastLogin
     */
    public function setUserLastLogin($user_lastLogin)
    {
        $this->user_lastLogin = $user_lastLogin;
    }

    /**
     * @return string
     */
    public function getUserLastUpdate()
    {
        return $this->user_lastUpdate;
    }

    /**
     * @param string $user_lastUpdate
     */
    public function setUserLastUpdate($user_lastUpdate)
    {
        $this->user_lastUpdate = $user_lastUpdate;
    }

    /**
     * @return boolean
     */
    public function isUserIsMigrate()
    {
        return $this->user_isMigrate;
    }

    /**
     * @param boolean $user_isMigrate
     */
    public function setUserIsMigrate($user_isMigrate)
    {
        $this->user_isMigrate = $user_isMigrate;
    }

    /**
     * @return mixed
     */
    public function getUserPreferences()
    {
        return $this->user_preferences;
    }

    /**
     * @param mixed $user_preferences
     */
    public function setUserPreferences($user_preferences)
    {
        $this->user_preferences = $user_preferences;
    }

    /**
     * @return string
     */
    public function getUserEmail()
    {
        return $this->user_email;
    }

    /**
     * @param string $user_email
     */
    public function setUserEmail($user_email)
    {
        $this->user_email = $user_email;
    }

    /**
     * @return string
     */
    public function getUserNotes()
    {
        return $this->user_notes;
    }

    /**
     * @param string $user_notes
     */
    public function setUserNotes($user_notes)
    {
        $this->user_notes = $user_notes;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->user_groupId;
    }

    /**
     * @param int $user_groupId
     */
    public function setUserGroupId($user_groupId)
    {
        $this->user_groupId = $user_groupId;
    }

    /**
     * @return int
     */
    public function getUserProfileId()
    {
        return $this->user_profileId;
    }

    /**
     * @param int $user_profileId
     */
    public function setUserProfileId($user_profileId)
    {
        $this->user_profileId = $user_profileId;
    }

    /**
     * @return boolean
     */
    public function isUserIsAdminApp()
    {
        return $this->user_isAdminApp;
    }

    /**
     * @param boolean $user_isAdminApp
     */
    public function setUserIsAdminApp($user_isAdminApp)
    {
        $this->user_isAdminApp = $user_isAdminApp;
    }

    /**
     * @return boolean
     */
    public function isUserIsAdminAcc()
    {
        return $this->user_isAdminAcc;
    }

    /**
     * @param boolean $user_isAdminAcc
     */
    public function setUserIsAdminAcc($user_isAdminAcc)
    {
        $this->user_isAdminAcc = $user_isAdminAcc;
    }

    /**
     * @return boolean
     */
    public function isUserIsDisabled()
    {
        return $this->user_isDisabled;
    }

    /**
     * @param boolean $user_isDisabled
     */
    public function setUserIsDisabled($user_isDisabled)
    {
        $this->user_isDisabled = $user_isDisabled;
    }

    /**
     * @return boolean
     */
    public function isUserIsChangePass()
    {
        return $this->user_isChangePass;
    }

    /**
     * @param boolean $user_isChangePass
     */
    public function setUserIsChangePass($user_isChangePass)
    {
        $this->user_isChangePass = $user_isChangePass;
    }

    /**
     * @return boolean
     */
    public function isUserIsLdap()
    {
        return $this->user_isLdap;
    }

    /**
     * @param boolean $user_isLdap
     */
    public function setUserIsLdap($user_isLdap)
    {
        $this->user_isLdap = $user_isLdap;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->user_login;
    }

    /**
     * @param string $user_login
     */
    public function setUserLogin($user_login)
    {
        $this->user_login = $user_login;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @param string $user_name
     */
    public function setUserName($user_name)
    {
        $this->user_name = $user_name;
    }

    /**
     * @return string
     */
    public function getUsergroupName()
    {
        return $this->usergroup_name;
    }

    /**
     * @param string $usergroup_name
     */
    public function setUsergroupName($usergroup_name)
    {
        $this->usergroup_name = $usergroup_name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->user_name;
    }
}