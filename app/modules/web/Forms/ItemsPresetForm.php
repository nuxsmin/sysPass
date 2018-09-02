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
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemPreset\SessionTimeout;
use SP\DataModel\ItemPresetData;
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Services\ItemPreset\ItemPresetRequest;

/**
 * Class ItemsPresetForm
 *
 * @package SP\Modules\Web\Forms
 */
final class ItemsPresetForm extends FormBase implements FormInterface
{
    /**
     * @var ItemPresetRequest
     */
    protected $itemPresetRequest;

    /**
     * Validar el formulario
     *
     * @param $action
     *
     * @return ItemsPresetForm
     * @throws ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ITEMPRESET_CREATE:
            case ActionsInterface::ITEMPRESET_EDIT:
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
     * @throws ValidationException
     */
    protected function analyzeRequestData()
    {
        $itemPresetData = new ItemPresetData();

        if ($this->itemId > 0) {
            $itemPresetData->setId($this->itemId);
        }

        if ($userId = $this->request->analyzeInt('user_id')) {
            $itemPresetData->setUserId($userId);
        }

        if ($userGroupId = $this->request->analyzeInt('user_group_id')) {
            $itemPresetData->setUserGroupId($userGroupId);
        }

        if ($userProfileId = $this->request->analyzeInt('user_profile_id')) {
            $itemPresetData->setUserProfileId($userProfileId);
        }

        $itemPresetData->setFixed((int)$this->request->analyzeBool('fixed_enabled', false));
        $itemPresetData->setPriority($this->request->analyzeInt('priority'));
        $itemPresetData->setType($this->request->analyzeString('type'));

        switch ($itemPresetData->getType()) {
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION:
                $this->itemPresetRequest = new ItemPresetRequest($itemPresetData, $this->makePermissionPreset());
                break;
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE:
                $this->itemPresetRequest = new ItemPresetRequest($itemPresetData, $this->makePrivatePreset());
                break;
            case ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT:
                $this->itemPresetRequest = new ItemPresetRequest($itemPresetData, $this->makeSessionTimeoutreset());
                break;
            default:
                throw new ValidationException(__u('Tipo de valor no definido o incorrecto'));
        }
    }

    /**
     * @return \SP\DataModel\\SP\DataModel\ItemPreset\AccountPermission
     * @throws ValidationException
     */
    private function makePermissionPreset()
    {
        $accountPermission = new AccountPermission();
        $accountPermission->setUsersView($this->request->analyzeArray('users_view', null, []));
        $accountPermission->setUsersEdit($this->request->analyzeArray('users_edit', null, []));
        $accountPermission->setUserGroupsView($this->request->analyzeArray('user_groups_view', null, []));
        $accountPermission->setUserGroupsEdit($this->request->analyzeArray('user_groups_edit', null, []));

        if (!$accountPermission->hasItems()) {
            throw new ValidationException(__u('No hay permisos definidos'));
        }

        return $accountPermission;
    }

    /**
     * @return AccountPrivate
     */
    private function makePrivatePreset()
    {
        $accountPrivate = new AccountPrivate();
        $accountPrivate->setPrivateUser($this->request->analyzeBool('private_user_enabled', false));
        $accountPrivate->setPrivateGroup($this->request->analyzeBool('private_group_enabled', false));

        return $accountPrivate;
    }

    /**
     * @return SessionTimeout
     * @throws ValidationException
     */
    private function makeSessionTimeoutreset()
    {
        try {
            return new SessionTimeout(
                $this->request->analyzeString('ip_address'),
                $this->request->analyzeInt('timeout')
            );
        } catch (InvalidArgumentException $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        $itemPresetData = $this->itemPresetRequest->getItemPresetData();

        if (!$itemPresetData->getUserId()
            && !$itemPresetData->getUserGroupId()
            && !$itemPresetData->getUserProfileId()
        ) {
            throw new ValidationException(__u('Es necesario asignar un elemento del tipo usuario, grupo o perfil'));
        }
    }

    /**
     * @return ItemPresetRequest
     */
    public function getItemData()
    {
        return $this->itemPresetRequest;
    }
}