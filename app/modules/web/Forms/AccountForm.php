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

use SP\Core\Application;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Ports\RequestService;
use SP\Util\Chainable;

use function SP\__u;

/**
 * Class AccountForm
 */
final class AccountForm extends FormBase implements FormInterface
{
    private null|AccountCreateDto|AccountUpdateDto $accountDto = null;

    public function __construct(
        Application                           $application,
        RequestService                        $request,
        private readonly AccountPresetService $accountPresetService,
        ?int                                  $itemId = null
    ) {
        parent::__construct($application, $request, $itemId);
    }

    /**
     * Validar el formulario
     *
     * @param int $action
     * @param int|null $id
     *
     * @return FormInterface
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        $chain = new Chainable(fn() => $this->analyzeRequestData(), $this);

        $this->accountDto = match ($action) {
            AclActionsInterface::ACCOUNT_EDIT_PASS =>
            $chain->next(fn(AccountDto $dto) => $this->checkPassword($dto))
                  ->next(
                      fn(AccountDto $dto) => $this->accountPresetService->checkPasswordPreset(
                          $dto
                      )
                  )
                  ->resolve(),
            AclActionsInterface::ACCOUNT_EDIT =>
            $chain->next(fn(AccountDto $dto) => $this->analyzeItems($dto))
                  ->next(fn(AccountDto $dto) => $this->checkCommon($dto))
                  ->resolve(),
            AclActionsInterface::ACCOUNT_CREATE,
            AclActionsInterface::ACCOUNT_COPY =>
            $chain->next(fn(AccountDto $dto) => $this->analyzeItems($dto))
                  ->next(fn(AccountDto $dto) => $this->checkCommon($dto))
                  ->next(fn(AccountDto $dto) => $this->checkPassword($dto))
                  ->next(
                      fn(AccountDto $dto) => $this->accountPresetService->checkPasswordPreset(
                          $dto
                      )
                  )
                  ->resolve(),
            AclActionsInterface::ACCOUNTMGR_BULK_EDIT =>
            $chain->next(fn(AccountDto $dto) => $this->analyzeItems($dto))
                  ->next(fn(AccountDto $dto) => $this->analyzeBulkEdit($dto))
                  ->resolve()
        };

        return $this;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return AccountCreateDto|AccountUpdateDto
     * @throws SPException
     */
    private function analyzeRequestData(): AccountCreateDto|AccountUpdateDto
    {
        $properties = [
            'name' => $this->request->analyzeString('name'),
            'login' => $this->request->analyzeString('login'),
            'clientId' => $this->request->analyzeInt('client_id'),
            'categoryId' => $this->request->analyzeInt('category_id'),
            'pass' => $this->request->analyzeEncrypted('password'),
            'userId' => $this->request->analyzeInt('owner_id', $this->context->getUserData()->id),
            'url' => $this->request->analyzeString('url'),
            'notes' => $this->request->analyzeUnsafeString('notes'),
            'private' => (int)$this->request->analyzeBool('private_enabled', false),
            'privateGroup' => (int)$this->request->analyzeBool('private_group_enabled', false),
            'passDateChange' => $this->request->analyzeInt('password_date_expire_unix'),
            'parentId' => $this->request->analyzeInt('parent_account_id'),
            'userGroupId' => $this->request->analyzeInt('main_usergroup_id'),
        ];

        return $this->itemId === null ? AccountCreateDto::fromArray($properties) : AccountUpdateDto::fromArray(
            $properties
        );
    }

    /**
     * @throws ValidationException
     */
    private function checkPassword(AccountDto $accountDto): AccountDto
    {
        if ($accountDto->parentId > 0) {
            return $accountDto;
        }

        if (!$accountDto->pass) {
            throw new ValidationException(__u('A key is needed'));
        }

        if ($this->request->analyzeEncrypted('password_repeat') !== $accountDto->pass) {
            throw new ValidationException(__u('Passwords do not match'));
        }

        return $accountDto;
    }

    /**
     * @throws SPException
     */
    private function analyzeItems(AccountDto $accountDto): AccountDto
    {
        if ($this->request->analyzeInt('other_users_view_update') === 1) {
            $accountDto = $accountDto->withUsersView($this->request->analyzeArray('other_users_view', null, []));
        }

        if ($this->request->analyzeInt('other_users_edit_update') === 1) {
            $accountDto = $accountDto->withUsersEdit($this->request->analyzeArray('other_users_edit', null, []));
        }

        if ($this->request->analyzeInt('other_usergroups_view_update') === 1) {
            $accountDto =
                $accountDto->withUserGroupsView($this->request->analyzeArray('other_usergroups_view', null, []));
        }

        if ($this->request->analyzeInt('other_usergroups_edit_update') === 1) {
            $accountDto =
                $accountDto->withUserGroupsEdit($this->request->analyzeArray('other_usergroups_edit', null, []));
        }

        if ($this->request->analyzeInt('tags_update') === 1) {
            $accountDto = $accountDto->withTags($this->request->analyzeArray('tags', null, []));
        }

        return $accountDto;
    }

    /**
     * @throws ValidationException
     */
    private function checkCommon(AccountDto $accountDto): AccountDto
    {
        if (!$accountDto->name) {
            throw new ValidationException(__u('An account name needed'));
        }

        if (!$accountDto->clientId) {
            throw new ValidationException(__u('A client is needed'));
        }

        if (!$accountDto->categoryId) {
            throw new ValidationException(__u('A category is needed'));
        }

        return $accountDto;
    }

    /**
     * @throws SPException
     */
    private function analyzeBulkEdit(AccountDto $accountDto): AccountDto
    {
        if ($this->request->analyzeBool('clear_permission_users_view', false)) {
            $accountDto = $accountDto->withUsersView([]);
        }

        if ($this->request->analyzeBool('clear_permission_users_edit', false)) {
            $accountDto = $accountDto->withUsersEdit([]);
        }

        if ($this->request->analyzeBool('clear_permission_usergroups_view', false)) {
            $accountDto = $accountDto->withUserGroupsView([]);
        }

        if ($this->request->analyzeBool('clear_permission_usergroups_edit', false)) {
            $accountDto = $accountDto->withUserGroupsEdit([]);
        }

        return $accountDto;
    }

    public function getItemData(): AccountCreateDto|AccountUpdateDto|null
    {
        return $this->accountDto;
    }
}
