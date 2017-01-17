<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 * Class ProfileData
 *
 * @package SP\DataModel
 */
class ProfileData extends ProfileBaseData
{
    /**
     * @var bool
     */
    protected $accView = false;
    /**
     * @var bool
     */
    protected $accViewPass = false;
    /**
     * @var bool
     */
    protected $accViewHistory = false;
    /**
     * @var bool
     */
    protected $accEdit = false;
    /**
     * @var bool
     */
    protected $accEditPass = false;
    /**
     * @var bool
     */
    protected $accAdd = false;
    /**
     * @var bool
     */
    protected $accDelete = false;
    /**
     * @var bool
     */
    protected $accFiles = false;
    /**
     * @var bool
     */
    protected $accPrivate = false;
    /**
     * @var bool
     */
    protected $accPrivateGroup = false;
    /**
     * @var bool
     */
    protected $accPermission = false;
    /**
     * @var bool
     */
    protected $accPublicLinks = false;
    /**
     * @var bool
     */
    protected $accGlobalSearch = false;
    /**
     * @var bool
     */
    protected $configGeneral = false;
    /**
     * @var bool
     */
    protected $configEncryption = false;
    /**
     * @var bool
     */
    protected $configBackup = false;
    /**
     * @var bool
     */
    protected $configImport = false;
    /**
     * @var bool
     */
    protected $mgmUsers = false;
    /**
     * @var bool
     */
    protected $mgmGroups = false;
    /**
     * @var bool
     */
    protected $mgmProfiles = false;
    /**
     * @var bool
     */
    protected $mgmCategories = false;
    /**
     * @var bool
     */
    protected $mgmCustomers = false;
    /**
     * @var bool
     */
    protected $mgmApiTokens = false;
    /**
     * @var bool
     */
    protected $mgmPublicLinks = false;
    /**
     * @var bool
     */
    protected $mgmAccounts = false;
    /**
     * @var bool
     */
    protected $mgmTags = false;
    /**
     * @var bool
     */
    protected $mgmFiles = false;
    /**
     * @var bool
     */
    protected $evl = false;
    /**
     * @var bool
     */
    protected $mgmCustomFields = false;

    /**
     * @return boolean
     */
    public function isAccView()
    {
        return $this->accView;
    }

    /**
     * @param boolean $accView
     */
    public function setAccView($accView)
    {
        $this->accView = $accView;
    }

    /**
     * @return boolean
     */
    public function isAccViewPass()
    {
        return $this->accViewPass;
    }

    /**
     * @param boolean $accViewPass
     */
    public function setAccViewPass($accViewPass)
    {
        $this->accViewPass = $accViewPass;
    }

    /**
     * @return boolean
     */
    public function isAccViewHistory()
    {
        return $this->accViewHistory;
    }

    /**
     * @param boolean $accViewHistory
     */
    public function setAccViewHistory($accViewHistory)
    {
        $this->accViewHistory = $accViewHistory;
    }

    /**
     * @return boolean
     */
    public function isAccEdit()
    {
        return $this->accEdit;
    }

    /**
     * @param boolean $accEdit
     */
    public function setAccEdit($accEdit)
    {
        $this->accEdit = $accEdit;
    }

    /**
     * @return boolean
     */
    public function isAccEditPass()
    {
        return $this->accEditPass;
    }

    /**
     * @param boolean $accEditPass
     */
    public function setAccEditPass($accEditPass)
    {
        $this->accEditPass = $accEditPass;
    }

    /**
     * @return boolean
     */
    public function isAccAdd()
    {
        return $this->accAdd;
    }

    /**
     * @param boolean $accAdd
     */
    public function setAccAdd($accAdd)
    {
        $this->accAdd = $accAdd;
    }

    /**
     * @return boolean
     */
    public function isAccDelete()
    {
        return $this->accDelete;
    }

    /**
     * @param boolean $accDelete
     */
    public function setAccDelete($accDelete)
    {
        $this->accDelete = $accDelete;
    }

    /**
     * @return boolean
     */
    public function isAccFiles()
    {
        return $this->accFiles;
    }

    /**
     * @param boolean $accFiles
     */
    public function setAccFiles($accFiles)
    {
        $this->accFiles = $accFiles;
    }

    /**
     * @return boolean
     */
    public function isAccPublicLinks()
    {
        return $this->accPublicLinks;
    }

    /**
     * @param boolean $accPublicLinks
     */
    public function setAccPublicLinks($accPublicLinks)
    {
        $this->accPublicLinks = $accPublicLinks;
    }

    /**
     * @return boolean
     */
    public function isConfigGeneral()
    {
        return $this->configGeneral;
    }

    /**
     * @param boolean $configGeneral
     */
    public function setConfigGeneral($configGeneral)
    {
        $this->configGeneral = $configGeneral;
    }

    /**
     * @return boolean
     */
    public function isConfigEncryption()
    {
        return $this->configEncryption;
    }

    /**
     * @param boolean $configEncryption
     */
    public function setConfigEncryption($configEncryption)
    {
        $this->configEncryption = $configEncryption;
    }

    /**
     * @return boolean
     */
    public function isConfigBackup()
    {
        return $this->configBackup;
    }

    /**
     * @param boolean $configBackup
     */
    public function setConfigBackup($configBackup)
    {
        $this->configBackup = $configBackup;
    }

    /**
     * @return boolean
     */
    public function isConfigImport()
    {
        return $this->configImport;
    }

    /**
     * @param boolean $configImport
     */
    public function setConfigImport($configImport)
    {
        $this->configImport = $configImport;
    }

    /**
     * @return boolean
     */
    public function isMgmUsers()
    {
        return $this->mgmUsers;
    }

    /**
     * @param boolean $mgmUsers
     */
    public function setMgmUsers($mgmUsers)
    {
        $this->mgmUsers = $mgmUsers;
    }

    /**
     * @return boolean
     */
    public function isMgmGroups()
    {
        return $this->mgmGroups;
    }

    /**
     * @param boolean $mgmGroups
     */
    public function setMgmGroups($mgmGroups)
    {
        $this->mgmGroups = $mgmGroups;
    }

    /**
     * @return boolean
     */
    public function isMgmProfiles()
    {
        return $this->mgmProfiles;
    }

    /**
     * @param boolean $mgmProfiles
     */
    public function setMgmProfiles($mgmProfiles)
    {
        $this->mgmProfiles = $mgmProfiles;
    }

    /**
     * @return boolean
     */
    public function isMgmCategories()
    {
        return $this->mgmCategories;
    }

    /**
     * @param boolean $mgmCategories
     */
    public function setMgmCategories($mgmCategories)
    {
        $this->mgmCategories = $mgmCategories;
    }

    /**
     * @return boolean
     */
    public function isMgmCustomers()
    {
        return $this->mgmCustomers;
    }

    /**
     * @param boolean $mgmCustomers
     */
    public function setMgmCustomers($mgmCustomers)
    {
        $this->mgmCustomers = $mgmCustomers;
    }

    /**
     * @return boolean
     */
    public function isMgmApiTokens()
    {
        return $this->mgmApiTokens;
    }

    /**
     * @param boolean $mgmApiTokens
     */
    public function setMgmApiTokens($mgmApiTokens)
    {
        $this->mgmApiTokens = $mgmApiTokens;
    }

    /**
     * @return boolean
     */
    public function isMgmPublicLinks()
    {
        return $this->mgmPublicLinks;
    }

    /**
     * @param boolean $mgmPublicLinks
     */
    public function setMgmPublicLinks($mgmPublicLinks)
    {
        $this->mgmPublicLinks = $mgmPublicLinks;
    }

    /**
     * @return boolean
     */
    public function isEvl()
    {
        return $this->evl;
    }

    /**
     * @param boolean $evl
     */
    public function setEvl($evl)
    {
        $this->evl = $evl;
    }

    /**
     * @return boolean
     */
    public function isMgmCustomFields()
    {
        return $this->mgmCustomFields;
    }

    /**
     * @param boolean $mgmCustomFields
     */
    public function setMgmCustomFields($mgmCustomFields)
    {
        $this->mgmCustomFields = $mgmCustomFields;
    }

    /**
     * unserialize() checks for the presence of a function with the magic name __wakeup.
     * If present, this function can reconstruct any resources that the object may have.
     * The intended use of __wakeup is to reestablish any database connections that may have been lost during
     * serialization and perform other reinitialization tasks.
     *
     * @return void
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __wakeup()
    {
        // Para realizar la conversión de nombre de propiedades que empiezan por _
        foreach (get_object_vars($this) as $name => $value) {
            if ($name[0] === '_') {
                $newName = substr($name, 1);
                $this->$newName = $value;
            }
        }
    }

    /**
     * @return boolean
     */
    public function isAccPrivate()
    {
        return $this->accPrivate;
    }

    /**
     * @param boolean $accPrivate
     */
    public function setAccPrivate($accPrivate)
    {
        $this->accPrivate = $accPrivate;
    }

    /**
     * @return boolean
     */
    public function isAccPermission()
    {
        return $this->accPermission;
    }

    /**
     * @param boolean $accPermission
     */
    public function setAccPermission($accPermission)
    {
        $this->accPermission = $accPermission;
    }

    /**
     * @return boolean
     */
    public function isMgmAccounts()
    {
        return $this->mgmAccounts;
    }

    /**
     * @param boolean $mgmAccounts
     */
    public function setMgmAccounts($mgmAccounts)
    {
        $this->mgmAccounts = $mgmAccounts;
    }

    /**
     * @return boolean
     */
    public function isMgmTags()
    {
        return $this->mgmTags;
    }

    /**
     * @param boolean $mgmTags
     */
    public function setMgmTags($mgmTags)
    {
        $this->mgmTags = $mgmTags;
    }

    /**
     * @return boolean
     */
    public function isMgmFiles()
    {
        return $this->mgmFiles;
    }

    /**
     * @param boolean $mgmFiles
     */
    public function setMgmFiles($mgmFiles)
    {
        $this->mgmFiles = $mgmFiles;
    }

    /**
     * @return boolean
     */
    public function isAccGlobalSearch()
    {
        return $this->accGlobalSearch;
    }

    /**
     * @param boolean $accGlobalSearch
     */
    public function setAccGlobalSearch($accGlobalSearch)
    {
        $this->accGlobalSearch = $accGlobalSearch;
    }

    /**
     * @return bool
     */
    public function isAccPrivateGroup()
    {
        return $this->accPrivateGroup;
    }

    /**
     * @param bool $accPrivateGroup
     */
    public function setAccPrivateGroup($accPrivateGroup)
    {
        $this->accPrivateGroup = $accPrivateGroup;
    }
}