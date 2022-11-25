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

defined('APP_ROOT') || die();

/**
 * Class ProfileData
 *
 * @package SP\DataModel
 */
class ProfileData
{
    protected bool $accView          = false;
    protected bool $accViewPass      = false;
    protected bool $accViewHistory   = false;
    protected bool $accEdit          = false;
    protected bool $accEditPass      = false;
    protected bool $accAdd           = false;
    protected bool $accDelete        = false;
    protected bool $accFiles         = false;
    protected bool $accPrivate       = false;
    protected bool $accPrivateGroup  = false;
    protected bool $accPermission    = false;
    protected bool $accPublicLinks   = false;
    protected bool $accGlobalSearch  = false;
    protected bool $configGeneral    = false;
    protected bool $configEncryption = false;
    protected bool $configBackup     = false;
    protected bool $configImport     = false;
    protected bool $mgmUsers         = false;
    protected bool $mgmGroups        = false;
    protected bool $mgmProfiles      = false;
    protected bool $mgmCategories    = false;
    protected bool $mgmCustomers     = false;
    protected bool $mgmApiTokens     = false;
    protected bool $mgmPublicLinks   = false;
    protected bool $mgmAccounts      = false;
    protected bool $mgmTags          = false;
    protected bool $mgmFiles         = false;
    protected bool $mgmItemsPreset   = false;
    protected bool $evl              = false;
    protected bool $mgmCustomFields  = false;

    public function isAccView(): bool
    {
        return $this->accView;
    }

    /**
     * @param  bool  $accView
     *
     * @return ProfileData
     */
    public function setAccView(bool $accView): ProfileData
    {
        $this->accView = $accView;

        return $this;
    }

    public function isAccViewPass(): bool
    {
        return $this->accViewPass;
    }

    /**
     * @param  bool  $accViewPass
     *
     * @return ProfileData
     */
    public function setAccViewPass(bool $accViewPass): ProfileData
    {
        $this->accViewPass = $accViewPass;

        return $this;
    }

    public function isAccViewHistory(): bool
    {
        return $this->accViewHistory;
    }

    /**
     * @param  bool  $accViewHistory
     *
     * @return ProfileData
     */
    public function setAccViewHistory(bool $accViewHistory): ProfileData
    {
        $this->accViewHistory = $accViewHistory;

        return $this;
    }

    public function isAccEdit(): bool
    {
        return $this->accEdit;
    }

    /**
     * @param  bool  $accEdit
     *
     * @return ProfileData
     */
    public function setAccEdit(bool $accEdit): ProfileData
    {
        $this->accEdit = $accEdit;

        return $this;
    }

    public function isAccEditPass(): bool
    {
        return $this->accEditPass;
    }

    /**
     * @param  bool  $accEditPass
     *
     * @return ProfileData
     */
    public function setAccEditPass(bool $accEditPass): ProfileData
    {
        $this->accEditPass = $accEditPass;

        return $this;
    }

    public function isAccAdd(): bool
    {
        return $this->accAdd;
    }

    /**
     * @param  bool  $accAdd
     *
     * @return ProfileData
     */
    public function setAccAdd(bool $accAdd): ProfileData
    {
        $this->accAdd = $accAdd;

        return $this;
    }

    public function isAccDelete(): bool
    {
        return $this->accDelete;
    }

    /**
     * @param  bool  $accDelete
     *
     * @return ProfileData
     */
    public function setAccDelete(bool $accDelete): ProfileData
    {
        $this->accDelete = $accDelete;

        return $this;
    }

    public function isAccFiles(): bool
    {
        return $this->accFiles;
    }

    /**
     * @param  bool  $accFiles
     *
     * @return ProfileData
     */
    public function setAccFiles(bool $accFiles): ProfileData
    {
        $this->accFiles = $accFiles;

        return $this;
    }

    public function isAccPrivate(): bool
    {
        return $this->accPrivate;
    }

    /**
     * @param  bool  $accPrivate
     *
     * @return ProfileData
     */
    public function setAccPrivate(bool $accPrivate): ProfileData
    {
        $this->accPrivate = $accPrivate;

        return $this;
    }

    public function isAccPrivateGroup(): bool
    {
        return $this->accPrivateGroup;
    }

    /**
     * @param  bool  $accPrivateGroup
     *
     * @return ProfileData
     */
    public function setAccPrivateGroup(bool $accPrivateGroup): ProfileData
    {
        $this->accPrivateGroup = $accPrivateGroup;

        return $this;
    }

    public function isAccPermission(): bool
    {
        return $this->accPermission;
    }

    /**
     * @param  bool  $accPermission
     *
     * @return ProfileData
     */
    public function setAccPermission(bool $accPermission): ProfileData
    {
        $this->accPermission = $accPermission;

        return $this;
    }

    public function isAccPublicLinks(): bool
    {
        return $this->accPublicLinks;
    }

    /**
     * @param  bool  $accPublicLinks
     *
     * @return ProfileData
     */
    public function setAccPublicLinks(bool $accPublicLinks): ProfileData
    {
        $this->accPublicLinks = $accPublicLinks;

        return $this;
    }

    public function isAccGlobalSearch(): bool
    {
        return $this->accGlobalSearch;
    }

    /**
     * @param  bool  $accGlobalSearch
     *
     * @return ProfileData
     */
    public function setAccGlobalSearch(bool $accGlobalSearch): ProfileData
    {
        $this->accGlobalSearch = $accGlobalSearch;

        return $this;
    }

    public function isConfigGeneral(): bool
    {
        return $this->configGeneral;
    }

    /**
     * @param  bool  $configGeneral
     *
     * @return ProfileData
     */
    public function setConfigGeneral(bool $configGeneral): ProfileData
    {
        $this->configGeneral = $configGeneral;

        return $this;
    }

    public function isConfigEncryption(): bool
    {
        return $this->configEncryption;
    }

    /**
     * @param  bool  $configEncryption
     *
     * @return ProfileData
     */
    public function setConfigEncryption(bool $configEncryption): ProfileData
    {
        $this->configEncryption = $configEncryption;

        return $this;
    }

    public function isConfigBackup(): bool
    {
        return $this->configBackup;
    }

    /**
     * @param  bool  $configBackup
     *
     * @return ProfileData
     */
    public function setConfigBackup(bool $configBackup): ProfileData
    {
        $this->configBackup = $configBackup;

        return $this;
    }

    public function isConfigImport(): bool
    {
        return $this->configImport;
    }

    /**
     * @param  bool  $configImport
     *
     * @return ProfileData
     */
    public function setConfigImport(bool $configImport): ProfileData
    {
        $this->configImport = $configImport;

        return $this;
    }

    public function isMgmUsers(): bool
    {
        return $this->mgmUsers;
    }

    /**
     * @param  bool  $mgmUsers
     *
     * @return ProfileData
     */
    public function setMgmUsers(bool $mgmUsers): ProfileData
    {
        $this->mgmUsers = $mgmUsers;

        return $this;
    }

    public function isMgmGroups(): bool
    {
        return $this->mgmGroups;
    }

    /**
     * @param  bool  $mgmGroups
     *
     * @return ProfileData
     */
    public function setMgmGroups(bool $mgmGroups): ProfileData
    {
        $this->mgmGroups = $mgmGroups;

        return $this;
    }

    public function isMgmProfiles(): bool
    {
        return $this->mgmProfiles;
    }

    /**
     * @param  bool  $mgmProfiles
     *
     * @return ProfileData
     */
    public function setMgmProfiles(bool $mgmProfiles): ProfileData
    {
        $this->mgmProfiles = $mgmProfiles;

        return $this;
    }

    public function isMgmCategories(): bool
    {
        return $this->mgmCategories;
    }

    /**
     * @param  bool  $mgmCategories
     *
     * @return ProfileData
     */
    public function setMgmCategories(bool $mgmCategories): ProfileData
    {
        $this->mgmCategories = $mgmCategories;

        return $this;
    }

    public function isMgmCustomers(): bool
    {
        return $this->mgmCustomers;
    }

    /**
     * @param  bool  $mgmCustomers
     *
     * @return ProfileData
     */
    public function setMgmCustomers(bool $mgmCustomers): ProfileData
    {
        $this->mgmCustomers = $mgmCustomers;

        return $this;
    }

    public function isMgmApiTokens(): bool
    {
        return $this->mgmApiTokens;
    }

    /**
     * @param  bool  $mgmApiTokens
     *
     * @return ProfileData
     */
    public function setMgmApiTokens(bool $mgmApiTokens): ProfileData
    {
        $this->mgmApiTokens = $mgmApiTokens;

        return $this;
    }

    public function isMgmPublicLinks(): bool
    {
        return $this->mgmPublicLinks;
    }

    /**
     * @param  bool  $mgmPublicLinks
     *
     * @return ProfileData
     */
    public function setMgmPublicLinks(bool $mgmPublicLinks): ProfileData
    {
        $this->mgmPublicLinks = $mgmPublicLinks;

        return $this;
    }

    public function isMgmAccounts(): bool
    {
        return $this->mgmAccounts;
    }

    /**
     * @param  bool  $mgmAccounts
     *
     * @return ProfileData
     */
    public function setMgmAccounts(bool $mgmAccounts): ProfileData
    {
        $this->mgmAccounts = $mgmAccounts;

        return $this;
    }

    public function isMgmTags(): bool
    {
        return $this->mgmTags;
    }

    /**
     * @param  bool  $mgmTags
     *
     * @return ProfileData
     */
    public function setMgmTags(bool $mgmTags): ProfileData
    {
        $this->mgmTags = $mgmTags;

        return $this;
    }

    public function isMgmFiles(): bool
    {
        return $this->mgmFiles;
    }

    /**
     * @param  bool  $mgmFiles
     *
     * @return ProfileData
     */
    public function setMgmFiles(bool $mgmFiles): ProfileData
    {
        $this->mgmFiles = $mgmFiles;

        return $this;
    }

    public function isMgmItemsPreset(): bool
    {
        return $this->mgmItemsPreset;
    }

    /**
     * @param  bool  $mgmItemsPreset
     *
     * @return ProfileData
     */
    public function setMgmItemsPreset(bool $mgmItemsPreset): ProfileData
    {
        $this->mgmItemsPreset = $mgmItemsPreset;

        return $this;
    }

    public function isEvl(): bool
    {
        return $this->evl;
    }

    /**
     * @param  bool  $evl
     *
     * @return ProfileData
     */
    public function setEvl(bool $evl): ProfileData
    {
        $this->evl = $evl;

        return $this;
    }

    public function isMgmCustomFields(): bool
    {
        return $this->mgmCustomFields;
    }

    /**
     * @param  bool  $mgmCustomFields
     *
     * @return ProfileData
     */
    public function setMgmCustomFields(bool $mgmCustomFields): ProfileData
    {
        $this->mgmCustomFields = $mgmCustomFields;

        return $this;
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
                $this->{substr($name, 1)} = $value;
            }
        }
    }
}
