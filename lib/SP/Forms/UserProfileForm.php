<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ProfileData;
use SP\Http\Request;

/**
 * Class UserProfileForm
 *
 * @package SP\Forms
 */
class UserProfileForm extends FormBase implements FormInterface
{
    /**
     * @var ProfileData
     */
    protected $profileData;

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
        $this->profileData = new ProfileData();
        $this->profileData->setName(Request::analyze('profile_name'));
        $this->profileData->setId($this->itemId);
        $this->profileData->setAccAdd(Request::analyze('profile_accadd', 0, false, 1));
        $this->profileData->setAccView(Request::analyze('profile_accview', 0, false, 1));
        $this->profileData->setAccViewPass(Request::analyze('profile_accviewpass', 0, false, 1));
        $this->profileData->setAccViewHistory(Request::analyze('profile_accviewhistory', 0, false, 1));
        $this->profileData->setAccEdit(Request::analyze('profile_accedit', 0, false, 1));
        $this->profileData->setAccEditPass(Request::analyze('profile_acceditpass', 0, false, 1));
        $this->profileData->setAccDelete(Request::analyze('profile_accdel', 0, false, 1));
        $this->profileData->setAccFiles(Request::analyze('profile_accfiles', 0, false, 1));
        $this->profileData->setAccPublicLinks(Request::analyze('profile_accpublinks', 0, false, 1));
        $this->profileData->setAccPrivate(Request::analyze('profile_accprivate', 0, false, 1));
        $this->profileData->setAccPrivateGroup(Request::analyze('profile_accprivategroup', 0, false, 1));
        $this->profileData->setAccPermission(Request::analyze('profile_accpermissions', 0, false, 1));
        $this->profileData->setAccGlobalSearch(Request::analyze('profile_accglobalsearch', 0, false, 1));
        $this->profileData->setConfigGeneral(Request::analyze('profile_config', 0, false, 1));
        $this->profileData->setConfigEncryption(Request::analyze('profile_configmpw', 0, false, 1));
        $this->profileData->setConfigBackup(Request::analyze('profile_configback', 0, false, 1));
        $this->profileData->setConfigImport(Request::analyze('profile_configimport', 0, false, 1));
        $this->profileData->setMgmCategories(Request::analyze('profile_categories', 0, false, 1));
        $this->profileData->setMgmCustomers(Request::analyze('profile_customers', 0, false, 1));
        $this->profileData->setMgmCustomFields(Request::analyze('profile_customfields', 0, false, 1));
        $this->profileData->setMgmUsers(Request::analyze('profile_users', 0, false, 1));
        $this->profileData->setMgmGroups(Request::analyze('profile_groups', 0, false, 1));
        $this->profileData->setMgmProfiles(Request::analyze('profile_profiles', 0, false, 1));
        $this->profileData->setMgmApiTokens(Request::analyze('profile_apitokens', 0, false, 1));
        $this->profileData->setMgmPublicLinks(Request::analyze('profile_publinks', 0, false, 1));
        $this->profileData->setMgmAccounts(Request::analyze('profile_accounts', 0, false, 1));
        $this->profileData->setMgmFiles(Request::analyze('profile_files', 0, false, 1));
        $this->profileData->setMgmTags(Request::analyze('profile_tags', 0, false, 1));
        $this->profileData->setEvl(Request::analyze('profile_eventlog', 0, false, 1));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->profileData->getName()) {
            throw new ValidationException(__u('Es necesario un nombre de perfil'));
        }
    }

    /**
     * @return ProfileData
     */
    public function getItemData()
    {
        return $this->profileData;
    }
}