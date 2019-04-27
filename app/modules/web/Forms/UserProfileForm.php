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

namespace SP\Modules\Web\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;

/**
 * Class UserProfileForm
 *
 * @package SP\Modules\Web\Forms
 */
final class UserProfileForm extends FormBase implements FormInterface
{
    /**
     * @var UserProfileData
     */
    protected $userProfileData;

    /**
     * Validar el formulario
     *
     * @param $action
     *
     * @return UserProfileForm
     * @throws ValidationException
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

        return $this;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $profileData = new ProfileData();
        $profileData->setAccAdd($this->request->analyzeBool('profile_accadd', false));
        $profileData->setAccView($this->request->analyzeBool('profile_accview', false));
        $profileData->setAccViewPass($this->request->analyzeBool('profile_accviewpass', false));
        $profileData->setAccViewHistory($this->request->analyzeBool('profile_accviewhistory', false));
        $profileData->setAccEdit($this->request->analyzeBool('profile_accedit', false));
        $profileData->setAccEditPass($this->request->analyzeBool('profile_acceditpass', false));
        $profileData->setAccDelete($this->request->analyzeBool('profile_accdel', false));
        $profileData->setAccFiles($this->request->analyzeBool('profile_accfiles', false));
        $profileData->setAccPublicLinks($this->request->analyzeBool('profile_accpublinks', false));
        $profileData->setAccPrivate($this->request->analyzeBool('profile_accprivate', false));
        $profileData->setAccPrivateGroup($this->request->analyzeBool('profile_accprivategroup', false));
        $profileData->setAccPermission($this->request->analyzeBool('profile_accpermissions', false));
        $profileData->setAccGlobalSearch($this->request->analyzeBool('profile_accglobalsearch', false));
        $profileData->setConfigGeneral($this->request->analyzeBool('profile_config', false));
        $profileData->setConfigEncryption($this->request->analyzeBool('profile_configmpw', false));
        $profileData->setConfigBackup($this->request->analyzeBool('profile_configback', false));
        $profileData->setConfigImport($this->request->analyzeBool('profile_configimport', false));
        $profileData->setMgmCategories($this->request->analyzeBool('profile_categories', false));
        $profileData->setMgmCustomers($this->request->analyzeBool('profile_customers', false));
        $profileData->setMgmCustomFields($this->request->analyzeBool('profile_customfields', false));
        $profileData->setMgmUsers($this->request->analyzeBool('profile_users', false));
        $profileData->setMgmGroups($this->request->analyzeBool('profile_groups', false));
        $profileData->setMgmProfiles($this->request->analyzeBool('profile_profiles', false));
        $profileData->setMgmApiTokens($this->request->analyzeBool('profile_apitokens', false));
        $profileData->setMgmPublicLinks($this->request->analyzeBool('profile_publinks', false));
        $profileData->setMgmAccounts($this->request->analyzeBool('profile_accounts', false));
        $profileData->setMgmFiles($this->request->analyzeBool('profile_files', false));
        $profileData->setMgmItemsPreset($this->request->analyzeBool('profile_items_preset', false));
        $profileData->setMgmTags($this->request->analyzeBool('profile_tags', false));
        $profileData->setEvl($this->request->analyzeBool('profile_eventlog', false));

        $this->userProfileData = new UserProfileData();
        $this->userProfileData->setName($this->request->analyzeString('profile_name'));
        $this->userProfileData->setId($this->itemId);
        $this->userProfileData->setProfile($profileData);
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->userProfileData->getName()) {
            throw new ValidationException(__u('A profile name is needed'));
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