<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Http\Request;

/**
 * Class UserProfileForm
 *
 * @package SP\Forms
 */
class UserProfileForm extends FormBase implements FormInterface
{
    /**
     * @var UserProfileData
     */
    protected $userProfileData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::PROFILE_CREATE:
            case ActionsInterface::PROFILE_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
        }

        return true;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $profileData = new ProfileData();
        $profileData->setAccAdd(Request::analyze('profile_accadd', 0, false, 1));
        $profileData->setAccView(Request::analyze('profile_accview', 0, false, 1));
        $profileData->setAccViewPass(Request::analyze('profile_accviewpass', 0, false, 1));
        $profileData->setAccViewHistory(Request::analyze('profile_accviewhistory', 0, false, 1));
        $profileData->setAccEdit(Request::analyze('profile_accedit', 0, false, 1));
        $profileData->setAccEditPass(Request::analyze('profile_acceditpass', 0, false, 1));
        $profileData->setAccDelete(Request::analyze('profile_accdel', 0, false, 1));
        $profileData->setAccFiles(Request::analyze('profile_accfiles', 0, false, 1));
        $profileData->setAccPublicLinks(Request::analyze('profile_accpublinks', 0, false, 1));
        $profileData->setAccPrivate(Request::analyze('profile_accprivate', 0, false, 1));
        $profileData->setAccPrivateGroup(Request::analyze('profile_accprivategroup', 0, false, 1));
        $profileData->setAccPermission(Request::analyze('profile_accpermissions', 0, false, 1));
        $profileData->setAccGlobalSearch(Request::analyze('profile_accglobalsearch', 0, false, 1));
        $profileData->setConfigGeneral(Request::analyze('profile_config', 0, false, 1));
        $profileData->setConfigEncryption(Request::analyze('profile_configmpw', 0, false, 1));
        $profileData->setConfigBackup(Request::analyze('profile_configback', 0, false, 1));
        $profileData->setConfigImport(Request::analyze('profile_configimport', 0, false, 1));
        $profileData->setMgmCategories(Request::analyze('profile_categories', 0, false, 1));
        $profileData->setMgmCustomers(Request::analyze('profile_customers', 0, false, 1));
        $profileData->setMgmCustomFields(Request::analyze('profile_customfields', 0, false, 1));
        $profileData->setMgmUsers(Request::analyze('profile_users', 0, false, 1));
        $profileData->setMgmGroups(Request::analyze('profile_groups', 0, false, 1));
        $profileData->setMgmProfiles(Request::analyze('profile_profiles', 0, false, 1));
        $profileData->setMgmApiTokens(Request::analyze('profile_apitokens', 0, false, 1));
        $profileData->setMgmPublicLinks(Request::analyze('profile_publinks', 0, false, 1));
        $profileData->setMgmAccounts(Request::analyze('profile_accounts', 0, false, 1));
        $profileData->setMgmFiles(Request::analyze('profile_files', 0, false, 1));
        $profileData->setMgmTags(Request::analyze('profile_tags', 0, false, 1));
        $profileData->setEvl(Request::analyze('profile_eventlog', 0, false, 1));

        $this->userProfileData = new UserProfileData();
        $this->userProfileData->setName(Request::analyze('profile_name'));
        $this->userProfileData->setId($this->itemId);
        $this->userProfileData->setProfile($profileData);
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->userProfileData->getName()) {
            throw new ValidationException(__u('Es necesario un nombre de perfil'));
        }
    }

    /**
     * @return UserProfileData
     */
    public function getItemData()
    {
        return $this->userProfileData;
    }
}