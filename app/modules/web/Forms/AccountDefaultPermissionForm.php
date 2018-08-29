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
use SP\DataModel\AccountDefaultPermissionData;
use SP\DataModel\AccountPermission;

/**
 * Class AccountDefaultPermissionForm
 *
 * @package SP\Modules\Web\Forms
 */
final class AccountDefaultPermissionForm extends FormBase implements FormInterface
{
    /**
     * @var AccountDefaultPermissionData
     */
    protected $accountDefaultPermissionData;

    /**
     * Validar el formulario
     *
     * @param $action
     *
     * @return AccountDefaultPermissionForm
     * @throws ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ACCOUNT_DEFAULT_PERMISSION_CREATE:
            case ActionsInterface::ACCOUNT_DEFAULT_PERMISSION_EDIT:
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
        $this->accountDefaultPermissionData = new AccountDefaultPermissionData();

        if ($this->itemId > 0) {
            $this->accountDefaultPermissionData->setId($this->itemId);
        }

        if ($userId = $this->request->analyzeInt('user_id')) {
            $this->accountDefaultPermissionData->setUserId($userId);
        }

        if ($userGroupId = $this->request->analyzeInt('user_group_id')) {
            $this->accountDefaultPermissionData->setUserGroupId($userGroupId);
        }

        if ($userProfileId = $this->request->analyzeInt('user_profile_id')) {
            $this->accountDefaultPermissionData->setUserProfileId($userProfileId);
        }
        
        $this->accountDefaultPermissionData->setFixed((int)$this->request->analyzeBool('fixed_enabled', false));
        $this->accountDefaultPermissionData->setPriority($this->request->analyzeInt('priority'));

        $accountPermission = new AccountPermission();
        $accountPermission->setUsersView($this->request->analyzeArray('users_view', null, []));
        $accountPermission->setUsersEdit($this->request->analyzeArray('users_edit', null, []));
        $accountPermission->setUserGroupsView($this->request->analyzeArray('user_groups_view', null, []));
        $accountPermission->setUserGroupsEdit($this->request->analyzeArray('user_groups_edit', null, []));

        $this->accountDefaultPermissionData->setAccountPermission($accountPermission);
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->accountDefaultPermissionData->getUserId()
            && !$this->accountDefaultPermissionData->getUserGroupId()
            && !$this->accountDefaultPermissionData->getUserProfileId()
        ) {
            throw new ValidationException(__u('Es necesario asignar un elemento del tipo usuario, grupo o perfil'));
        }

        if (!$this->accountDefaultPermissionData->getAccountPermission()->hasItems()) {
            throw new ValidationException(__u('No hay permisos definidos'));
        }
    }

    /**
     * @return AccountDefaultPermissionData
     */
    public function getItemData()
    {
        return $this->accountDefaultPermissionData;
    }
}