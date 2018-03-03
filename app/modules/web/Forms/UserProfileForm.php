<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Modules\Web\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Http\Request;

/**
 * Class UserProfileForm
 *
 * @package SP\Modules\Web\Forms
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
        $profileData->setAccAdd(Request::analyzeBool('profile_accadd', false));
        $profileData->setAccView(Request::analyzeBool('profile_accview', false));
        $profileData->setAccViewPass(Request::analyzeBool('profile_accviewpass', false));
        $profileData->setAccViewHistory(Request::analyzeBool('profile_accviewhistory', false));
        $profileData->setAccEdit(Request::analyzeBool('profile_accedit', false));
        $profileData->setAccEditPass(Request::analyzeBool('profile_acceditpass', false));
        $profileData->setAccDelete(Request::analyzeBool('profile_accdel', false));
        $profileData->setAccFiles(Request::analyzeBool('profile_accfiles', false));
        $profileData->setAccPublicLinks(Request::analyzeBool('profile_accpublinks', false));
        $profileData->setAccPrivate(Request::analyzeBool('profile_accprivate', false));
        $profileData->setAccPrivateGroup(Request::analyzeBool('profile_accprivategroup', false));
        $profileData->setAccPermission(Request::analyzeBool('profile_accpermissions', false));
        $profileData->setAccGlobalSearch(Request::analyzeBool('profile_accglobalsearch', false));
        $profileData->setConfigGeneral(Request::analyzeBool('profile_config', false));
        $profileData->setConfigEncryption(Request::analyzeBool('profile_configmpw', false));
        $profileData->setConfigBackup(Request::analyzeBool('profile_configback', false));
        $profileData->setConfigImport(Request::analyzeBool('profile_configimport', false));
        $profileData->setMgmCategories(Request::analyzeBool('profile_categories', false));
        $profileData->setMgmCustomers(Request::analyzeBool('profile_customers', false));
        $profileData->setMgmCustomFields(Request::analyzeBool('profile_customfields', false));
        $profileData->setMgmUsers(Request::analyzeBool('profile_users', false));
        $profileData->setMgmGroups(Request::analyzeBool('profile_groups', false));
        $profileData->setMgmProfiles(Request::analyzeBool('profile_profiles', false));
        $profileData->setMgmApiTokens(Request::analyzeBool('profile_apitokens', false));
        $profileData->setMgmPublicLinks(Request::analyzeBool('profile_publinks', false));
        $profileData->setMgmAccounts(Request::analyzeBool('profile_accounts', false));
        $profileData->setMgmFiles(Request::analyzeBool('profile_files', false));
        $profileData->setMgmTags(Request::analyzeBool('profile_tags', false));
        $profileData->setEvl(Request::analyzeBool('profile_eventlog', false));

        $this->userProfileData = new UserProfileData();
        $this->userProfileData->setName(Request::analyzeString('profile_name'));
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