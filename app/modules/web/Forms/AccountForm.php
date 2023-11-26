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
use SP\Core\Exceptions\ValidationException;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Ports\AccountPresetServiceInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Http\RequestInterface;
use SP\Util\Chainable;

use function SP\__u;

/**
 * Class AccountForm
 */
final class AccountForm extends FormBase implements FormInterface
{
    private AccountPresetServiceInterface          $accountPresetService;
    private null|AccountCreateDto|AccountUpdateDto $accountDto = null;

    public function __construct(
        Application $application,
        RequestInterface $request,
        AccountPresetServiceInterface $accountPresetService,
        ?int $itemId = null
    ) {
        parent::__construct($application, $request, $itemId);

        $this->accountPresetService = $accountPresetService;
    }

    /**
     * Validar el formulario
     *
     * @param  int  $action
     * @param  int|null  $id
     *
     * @return FormInterface
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        $chain = new Chainable(fn() => $this->analyzeRequestData(), $this);

        switch ($action) {
            case AclActionsInterface::ACCOUNT_EDIT_PASS:
                $this->accountDto = $chain->next(fn(AccountDto $dto) => $this->checkPassword($dto))
                                          ->next(
                                              fn(AccountDto $dto) => $this->accountPresetService->checkPasswordPreset(
                                                  $dto
                                              )
                                          )
                                          ->resolve();
                break;
            case AclActionsInterface::ACCOUNT_EDIT:
                $this->accountDto = $chain->next(fn(AccountDto $dto) => $this->analyzeItems($dto))
                                          ->next(fn(AccountDto $dto) => $this->checkCommon($dto))
                                          ->resolve();
                break;
            case AclActionsInterface::ACCOUNT_CREATE:
            case AclActionsInterface::ACCOUNT_COPY:
                $this->accountDto = $chain->next(fn(AccountDto $dto) => $this->analyzeItems($dto))
                                          ->next(fn(AccountDto $dto) => $this->checkCommon($dto))
                                          ->next(fn(AccountDto $dto) => $this->checkPassword($dto))
                                          ->next(
                                              fn(AccountDto $dto) => $this->accountPresetService->checkPasswordPreset(
                                                  $dto
                                              )
                                          )
                                          ->resolve();
                break;
            case AclActionsInterface::ACCOUNTMGR_BULK_EDIT:
                $this->accountDto = $chain->next(fn(AccountDto $dto) => $this->analyzeItems($dto))
                                          ->next(fn(AccountDto $dto) => $this->analyzeBulkEdit($dto))
                                          ->resolve();
                break;
        }

        return $this;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return AccountCreateDto|AccountUpdateDto
     */
    private function analyzeRequestData(): AccountCreateDto|AccountUpdateDto
    {
        $name = $this->request->analyzeString('name');
        $login = $this->request->analyzeString('login');
        $clientId = $this->request->analyzeInt('client_id');
        $categoryId = $this->request->analyzeInt('category_id');
        $password = $this->request->analyzeEncrypted('password');
        $userId = $this->request->analyzeInt('owner_id');
        $url = $this->request->analyzeString('url');
        $notes = $this->request->analyzeUnsafeString('notes');
        $private = (int)$this->request->analyzeBool('private_enabled', false);
        $privateGroup = (int)$this->request->analyzeBool('private_group_enabled', false);
        $passDateChange = $this->request->analyzeInt('password_date_expire_unix');
        $parentId = $this->request->analyzeInt('parent_account_id');
        $userGroupId = $this->request->analyzeInt('main_usergroup_id');

        if (null === $this->itemId) {
            $accountDto = new AccountCreateDto(
                $name,
                $login,
                $clientId,
                $categoryId,
                $password,
                $userId,
                null,
                $url,
                $notes,
                $this->context->getUserData()->getId(),
                $private,
                $privateGroup,
                $passDateChange,
                $parentId,
                $userGroupId
            );
        } else {
            $accountDto = new AccountUpdateDto(
                $this->itemId,
                $name,
                $login,
                $clientId,
                $categoryId,
                $password,
                $userId,
                null,
                $url,
                $notes,
                $this->context->getUserData()->getId(),
                $private,
                $privateGroup,
                $passDateChange,
                $parentId,
                $userGroupId
            );
        }

        return $accountDto;
    }

    /**
     * @throws ValidationException
     */
    private function checkPassword(AccountDto $accountDto): void
    {
        if ($accountDto->getParentId() > 0) {
            return;
        }

        if (!$accountDto->getPass()) {
            throw new ValidationException(__u('A key is needed'));
        }

        if ($this->request->analyzeEncrypted('password_repeat') !== $accountDto->getPass()) {
            throw new ValidationException(__u('Passwords do not match'));
        }
    }

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
        if (!$accountDto->getName()) {
            throw new ValidationException(__u('An account name needed'));
        }

        if (!$accountDto->getClientId()) {
            throw new ValidationException(__u('A client name needed'));
        }

        if (!$accountDto->getCategoryId()) {
            throw new ValidationException(__u('A category is needed'));
        }

        return $accountDto;
    }

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
