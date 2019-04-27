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

use Psr\Container\ContainerInterface;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\ValidationException;
use SP\Services\Account\AccountPresetService;
use SP\Services\Account\AccountRequest;

/**
 * Class AccountForm
 *
 * @package SP\Account
 */
final class AccountForm extends FormBase implements FormInterface
{
    /**
     * @var AccountRequest
     */
    protected $accountRequest;
    /**
     * @var AccountPresetService
     */
    private $accountPresetService;

    /**
     * Validar el formulario
     *
     * @param $action
     *
     * @return AccountForm
     * @throws ValidationException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ACCOUNT_EDIT_PASS:
                $this->analyzeRequestData();
                $this->checkPassword();
                $this->accountPresetService->checkPasswordPreset($this->accountRequest);
                break;
            case ActionsInterface::ACCOUNT_EDIT:
                $this->analyzeRequestData();
                $this->analyzeItems();
                $this->checkCommon();
                break;
            case ActionsInterface::ACCOUNT_CREATE:
            case ActionsInterface::ACCOUNT_COPY:
                $this->analyzeRequestData();
                $this->analyzeItems();
                $this->checkCommon();
                $this->checkPassword();
                $this->accountPresetService->checkPasswordPreset($this->accountRequest);
                break;
            case ActionsInterface::ACCOUNTMGR_BULK_EDIT:
                $this->analyzeRequestData();
                $this->analyzeItems();
                $this->analyzeBulkEdit();
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
        $this->accountRequest->id = $this->itemId;
        $this->accountRequest->name = $this->request->analyzeString('name');
        $this->accountRequest->clientId = $this->request->analyzeInt('client_id');
        $this->accountRequest->categoryId = $this->request->analyzeInt('category_id');
        $this->accountRequest->login = $this->request->analyzeString('login');
        $this->accountRequest->url = $this->request->analyzeString('url');
        $this->accountRequest->notes = $this->request->analyzeUnsafeString('notes');
        $this->accountRequest->userEditId = $this->context->getUserData()->getId();
        $this->accountRequest->pass = $this->request->analyzeEncrypted('password');
        $this->accountRequest->isPrivate = (int)$this->request->analyzeBool('private_enabled', false);
        $this->accountRequest->isPrivateGroup = (int)$this->request->analyzeBool('private_group_enabled', false);

        if ($this->request->analyzeInt('password_date_expire')) {
            $this->accountRequest->passDateChange = $this->request->analyzeInt('password_date_expire_unix');
        }

        $this->accountRequest->parentId = $this->request->analyzeInt('parent_account_id');
        $this->accountRequest->userId = $this->request->analyzeInt('owner_id');
        $this->accountRequest->userGroupId = $this->request->analyzeInt('main_usergroup_id');
    }

    /**
     * @throws ValidationException
     */
    private function checkPassword()
    {
        if ($this->accountRequest->parentId > 0) {
            return;
        }

        if (!$this->accountRequest->pass) {
            throw new ValidationException(__u('A key is needed'));
        }

        if ($this->request->analyzeEncrypted('password_repeat') !== $this->accountRequest->pass) {
            throw new ValidationException(__u('Passwords do not match'));
        }
    }

    /**
     * analyzeItems
     */
    private function analyzeItems()
    {
        if ($this->request->analyzeInt('other_users_view_update') === 1) {
            $this->accountRequest->usersView = $this->request->analyzeArray('other_users_view', null, []);
        }

        if ($this->request->analyzeInt('other_users_edit_update') === 1) {
            $this->accountRequest->usersEdit = $this->request->analyzeArray('other_users_edit', null, []);
        }

        if ($this->request->analyzeInt('other_usergroups_view_update') === 1) {
            $this->accountRequest->userGroupsView = $this->request->analyzeArray('other_usergroups_view', null, []);
        }

        if ($this->request->analyzeInt('other_usergroups_edit_update') === 1) {
            $this->accountRequest->userGroupsEdit = $this->request->analyzeArray('other_usergroups_edit', null, []);
        }

        if ($this->request->analyzeInt('tags_update') === 1) {
            $this->accountRequest->tags = $this->request->analyzeArray('tags', null, []);
        }
    }

    /**
     * @throws ValidationException
     */
    private function checkCommon()
    {
        if (!$this->accountRequest->name) {
            throw new ValidationException(__u('An account name needed'));
        }

        if (!$this->accountRequest->clientId) {
            throw new ValidationException(__u('A client name needed'));
        }

        if (!$this->accountRequest->categoryId) {
            throw new ValidationException(__u('A category is needed'));
        }
    }

    /**
     * analyzeBulkEdit
     */
    private function analyzeBulkEdit()
    {
        if ($this->request->analyzeBool('clear_permission_users_view', false)) {
            $this->accountRequest->usersView = [];
        }

        if ($this->request->analyzeBool('clear_permission_users_edit', false)) {
            $this->accountRequest->usersEdit = [];
        }

        if ($this->request->analyzeBool('clear_permission_usergroups_view', false)) {
            $this->accountRequest->userGroupsView = [];
        }

        if ($this->request->analyzeBool('clear_permission_usergroups_edit', false)) {
            $this->accountRequest->userGroupsEdit = [];
        }
    }

    /**
     * @return AccountRequest
     */
    public function getItemData()
    {
        return $this->accountRequest;
    }

    /**
     * @param ContainerInterface $dic
     */
    protected function initialize($dic)
    {
        $this->accountPresetService = $dic->get(AccountPresetService::class);
        $this->accountRequest = new AccountRequest();
    }
}