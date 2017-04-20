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

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ProfileData;
use SP\Http\Request;

/**
 * Class ProfileForm
 *
 * @package SP\Forms
 */
class ProfileForm extends FormBase implements FormInterface
{
    /**
     * @var ProfileData
     */
    protected $ProfileData;

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
            case ActionsInterface::ACTION_USR_PROFILES_NEW:
            case ActionsInterface::ACTION_USR_PROFILES_EDIT:
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
        $this->ProfileData = new ProfileData();
        $this->ProfileData->setUserprofileName(Request::analyze('profile_name'));
        $this->ProfileData->setUserprofileId(Request::analyze('itemId', 0));
        $this->ProfileData->setAccAdd(Request::analyze('profile_accadd', 0, false, 1));
        $this->ProfileData->setAccView(Request::analyze('profile_accview', 0, false, 1));
        $this->ProfileData->setAccViewPass(Request::analyze('profile_accviewpass', 0, false, 1));
        $this->ProfileData->setAccViewHistory(Request::analyze('profile_accviewhistory', 0, false, 1));
        $this->ProfileData->setAccEdit(Request::analyze('profile_accedit', 0, false, 1));
        $this->ProfileData->setAccEditPass(Request::analyze('profile_acceditpass', 0, false, 1));
        $this->ProfileData->setAccDelete(Request::analyze('profile_accdel', 0, false, 1));
        $this->ProfileData->setAccFiles(Request::analyze('profile_accfiles', 0, false, 1));
        $this->ProfileData->setAccPublicLinks(Request::analyze('profile_accpublinks', 0, false, 1));
        $this->ProfileData->setAccPrivate(Request::analyze('profile_accprivate', 0, false, 1));
        $this->ProfileData->setAccPrivateGroup(Request::analyze('profile_accprivategroup', 0, false, 1));
        $this->ProfileData->setAccPermission(Request::analyze('profile_accpermissions', 0, false, 1));
        $this->ProfileData->setAccGlobalSearch(Request::analyze('profile_accglobalsearch', 0, false, 1));
        $this->ProfileData->setConfigGeneral(Request::analyze('profile_config', 0, false, 1));
        $this->ProfileData->setConfigEncryption(Request::analyze('profile_configmpw', 0, false, 1));
        $this->ProfileData->setConfigBackup(Request::analyze('profile_configback', 0, false, 1));
        $this->ProfileData->setConfigImport(Request::analyze('profile_configimport', 0, false, 1));
        $this->ProfileData->setMgmCategories(Request::analyze('profile_categories', 0, false, 1));
        $this->ProfileData->setMgmCustomers(Request::analyze('profile_customers', 0, false, 1));
        $this->ProfileData->setMgmCustomFields(Request::analyze('profile_customfields', 0, false, 1));
        $this->ProfileData->setMgmUsers(Request::analyze('profile_users', 0, false, 1));
        $this->ProfileData->setMgmGroups(Request::analyze('profile_groups', 0, false, 1));
        $this->ProfileData->setMgmProfiles(Request::analyze('profile_profiles', 0, false, 1));
        $this->ProfileData->setMgmApiTokens(Request::analyze('profile_apitokens', 0, false, 1));
        $this->ProfileData->setMgmPublicLinks(Request::analyze('profile_publinks', 0, false, 1));
        $this->ProfileData->setMgmAccounts(Request::analyze('profile_accounts', 0, false, 1));
        $this->ProfileData->setMgmFiles(Request::analyze('profile_files', 0, false, 1));
        $this->ProfileData->setMgmTags(Request::analyze('profile_tags', 0, false, 1));
        $this->ProfileData->setEvl(Request::analyze('profile_eventlog', 0, false, 1));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->ProfileData->getUserprofileName()) {
            throw new ValidationException(__('Es necesario un nombre de perfil', false));
        }
    }

    /**
     * @return ProfileData
     */
    public function getItemData()
    {
        return $this->ProfileData;
    }
}