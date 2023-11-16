<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Forms;

use SP\Core\Acl\AclActionsInterface;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemPreset\Password;
use SP\DataModel\ItemPreset\SessionTimeout;
use SP\Domain\Account\Models\ItemPreset;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Services\ItemPresetRequest;
use SP\Mvc\Controller\Validators\Validator;

/**
 * Class ItemsPresetForm
 *
 * @package SP\Modules\Web\Forms
 */
final class ItemsPresetForm extends FormBase implements FormInterface
{
    protected ?ItemPresetRequest $itemPresetRequest = null;

    /**
     * Validar el formulario
     *
     * @param  int  $action
     * @param  int|null  $id
     *
     * @return ItemsPresetForm|FormInterface
     * @throws ValidationException
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        switch ($action) {
            case AclActionsInterface::ITEMPRESET_CREATE:
            case AclActionsInterface::ITEMPRESET_EDIT:
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
    protected function analyzeRequestData(): void
    {
        $itemPresetData = new ItemPreset();

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
        $itemPresetData->setPriority($this->request->analyzeInt('priority', 0));
        $itemPresetData->setType($this->request->analyzeString('type'));

        switch ($itemPresetData->getType()) {
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION:
                $this->itemPresetRequest = new ItemPresetRequest($itemPresetData, $this->makePermissionPreset());
                break;
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE:
                $this->itemPresetRequest = new ItemPresetRequest($itemPresetData, $this->makePrivatePreset());
                break;
            case ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT:
                $this->itemPresetRequest = new ItemPresetRequest($itemPresetData, $this->makeSessionTimeoutPreset());
                break;
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD:
                $this->itemPresetRequest = new ItemPresetRequest($itemPresetData, $this->makePasswordPreset());
                break;
            default:
                throw new ValidationException(__u('Value type not set or incorrect'));
        }
    }

    /**
     * @return AccountPermission
     * @throws ValidationException
     */
    private function makePermissionPreset(): AccountPermission
    {
        $accountPermission = new AccountPermission();
        $accountPermission->setUsersView($this->request->analyzeArray('users_view', null, []));
        $accountPermission->setUsersEdit($this->request->analyzeArray('users_edit', null, []));
        $accountPermission->setUserGroupsView($this->request->analyzeArray('user_groups_view', null, []));
        $accountPermission->setUserGroupsEdit($this->request->analyzeArray('user_groups_edit', null, []));

        if (!$accountPermission->hasItems()) {
            throw new ValidationException(__u('There aren\'t any defined permissions'));
        }

        return $accountPermission;
    }

    /**
     * @return AccountPrivate
     */
    private function makePrivatePreset(): AccountPrivate
    {
        return new AccountPrivate(
            $this->request->analyzeBool('private_user_enabled', false),
            $this->request->analyzeBool('private_group_enabled', false)
        );
    }

    /**
     * @return SessionTimeout
     * @throws ValidationException
     */
    private function makeSessionTimeoutPreset(): SessionTimeout
    {
        try {
            return new SessionTimeout(
                $this->request->analyzeString('ip_address', ''),
                $this->request->analyzeInt('timeout', 0)
            );
        } catch (InvalidArgumentException $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    /**
     * @return Password
     * @throws ValidationException
     */
    private function makePasswordPreset(): Password
    {
        $regex = $this->request->analyzeUnsafeString('regex');

        if (!empty($regex) && Validator::isRegex($regex) === false) {
            throw new ValidationException(__u('Invalid regular expression'));
        }

        return new Password(
            $this->request->analyzeInt('length', 1),
            $this->request->analyzeBool('use_numbers_enabled', false),
            $this->request->analyzeBool('use_letters_enabled', false),
            $this->request->analyzeBool('use_symbols_enabled', false),
            $this->request->analyzeBool('use_upper_enabled', false),
            $this->request->analyzeBool('use_lower_enabled', false),
            $this->request->analyzeBool('use_image_enabled', false),
            $this->request->analyzeInt('expire_time', 0),
            $this->request->analyzeInt('score', 0),
            $regex
        );
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon(): void
    {
        $itemPresetData = $this->itemPresetRequest->getItemPresetData();

        if (!$itemPresetData->getUserId()
            && !$itemPresetData->getUserGroupId()
            && !$itemPresetData->getUserProfileId()
        ) {
            throw new ValidationException(__u('An element of type user, group or profile need to be set'));
        }
    }

    public function getItemData(): ?ItemPresetRequest
    {
        return $this->itemPresetRequest;
    }
}
