<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Account\Services;

use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Models\AccountItem;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountItemsService;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Ports\PublicLinkRepository;
use SP\Domain\Category\Models\Category as CategoryModel;
use SP\Domain\Category\Ports\CategoryRepository;
use SP\Domain\Client\Models\Client as ClientModel;
use SP\Domain\Client\Ports\ClientRepository;
use SP\Domain\Common\Models\Item;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\ItemPreset\Models\AccountPrivate;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserGroup as UserGroupModel;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Domain\User\Ports\UserGroupRepository;
use SP\Domain\User\Ports\UserRepository;
use SP\Domain\User\Ports\UserToUserGroupRepository;

use function SP\__u;

/**
 * Applies account mutations, history and preset rules.
 */
final class AccountWriter extends Service
{
    /**
     * @param CustomFieldDataService<CustomFieldDataModel>|null $customFieldDataService
     * @param PublicLinkRepository<PublicLinkModel>|null $publicLinkRepository
     * @param ClientRepository<ClientModel>|null $clientRepository
     * @param CategoryRepository<CategoryModel>|null $categoryRepository
     * @param UserRepository<UserModel>|null $userRepository
     * @param UserGroupRepository<UserGroupModel>|null $userGroupRepository
     * @param UserToUserGroupRepository<UserToUserGroupModel>|null $userToUserGroupRepository
     */
    public function __construct(
        ConfigFileService                                 $config,
        EventDispatcherInterface                          $eventDispatcher,
        Context                                           $context,
        private readonly AccountRepository                $accountRepository,
        private readonly ItemPresetService                $itemPresetService,
        private readonly AccountHistoryService            $accountHistoryService,
        private readonly AccountItemsService              $accountItemsService,
        private readonly AccountPresetService             $accountPresetService,
        private readonly ConfigService                    $configService,
        private readonly AccountCryptService              $accountCryptService,
        private readonly AccountReader                    $accountReader,
        private readonly ?CustomFieldDataService          $customFieldDataService,
        private readonly ?PublicLinkRepository            $publicLinkRepository,
        private readonly ?ClientRepository                $clientRepository,
        private readonly ?CategoryRepository              $categoryRepository,
        private readonly ?UserRepository                  $userRepository,
        private readonly ?UserGroupRepository             $userGroupRepository,
        private readonly ?AccountToTagRepository          $accountToTagRepository,
        private readonly ?AccountToUserRepository         $accountToUserRepository,
        private readonly ?AccountToUserGroupRepository    $accountToUserGroupRepository,
        private readonly ?AclInterface                    $acl = null,
        private readonly ?CryptInterface                  $crypt = null,
        private readonly ?MasterKeyStore                  $masterKeyStore = null,
        private readonly ?UserToUserGroupRepository       $userToUserGroupRepository = null
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * @throws ServiceException
     */
    public function updateBulk(AccountUpdateBulkDto $accountUpdateBulkDto): void
    {
        $dtos = $accountUpdateBulkDto->getAccountUpdateDto();

        if ($dtos === []) {
            return;
        }

        foreach (array_keys($dtos) as $accountId) {
            $this->checkAccountAccess($accountId);
        }

        foreach ($dtos as $accountUpdateDto) {
            if ($accountUpdateDto->clientId !== null
                && $accountUpdateDto->clientId > 0
                && $this->clientRepository !== null
                && $this->clientRepository->getById($accountUpdateDto->clientId)->getNumRows() === 0
            ) {
                throw new ValidationException(__u('A client is needed'));
            }

            if ($accountUpdateDto->categoryId !== null
                && $accountUpdateDto->categoryId > 0
                && $this->categoryRepository !== null
                && $this->categoryRepository->getById($accountUpdateDto->categoryId)->getNumRows() === 0
            ) {
                throw new ValidationException(__u('A category is needed'));
            }

            if ($accountUpdateDto->userId !== null
                && $accountUpdateDto->userId > 0
                && $this->userRepository !== null
                && $this->userRepository->getById($accountUpdateDto->userId)->getNumRows() === 0
            ) {
                throw new ValidationException(__u('An owner is needed'));
            }

            if ($accountUpdateDto->userGroupId !== null
                && $accountUpdateDto->userGroupId > 0
                && $this->userGroupRepository !== null
                && $this->userGroupRepository->getById($accountUpdateDto->userGroupId)->getNumRows() === 0
            ) {
                throw new ValidationException(__u('A user group is needed'));
            }
        }

        $this->accountRepository->transactionAware(
            function () use ($accountUpdateBulkDto, $dtos) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();

                $userCanChangePermissions = AccountAcl::getShowPermission($userData, $userProfile);

                if ($accountUpdateBulkDto->isDeleteHistory()) {
                    $this->accountHistoryService->deleteByAccountIdBatch(array_keys($dtos));
                }

                $itemPreset = $this->itemPresetService->getForCurrentUser(
                    ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE
                );
                $fixedPrivatePreset = ($itemPreset !== null && $itemPreset->getFixed())
                    ? $itemPreset->hydrate(AccountPrivate::class)
                    : null;

                foreach ($dtos as $accountId => $accountUpdateDto) {
                    $changeOwner = false;
                    $changeUserGroup = false;
                    $account = $this->accountReader->getById($accountId);

                    if ($userCanChangePermissions) {
                        $changeOwner = $this->userCanChangeOwner($userData, $userProfile, $account);
                        $changeUserGroup = $this->userCanChangeGroup($userData, $userProfile, $account);
                    }

                    $resolvedDto = $accountUpdateDto->userEditId === null
                        ? $accountUpdateDto->mutate(['userEditId' => $userData->id])
                        : $accountUpdateDto;
                    $updatePrivacy = false;

                    if ($fixedPrivatePreset !== null) {
                        $privateUser = $userData->id === (int)$account->getUserId()
                            && $fixedPrivatePreset->isPrivateUser();
                        $privateGroup = $userData->userGroupId === (int)$account->getUserGroupId()
                            && $fixedPrivatePreset->isPrivateGroup();

                        $resolvedDto = $resolvedDto
                            ->withPrivate($privateUser)
                            ->withPrivateGroup($privateGroup);
                        $updatePrivacy = true;
                    }

                    $hasChanged = $this->hasBulkChanges(
                        $accountId,
                        $account,
                        $resolvedDto,
                        $userCanChangePermissions,
                        $changeOwner,
                        $changeUserGroup,
                        $updatePrivacy
                    );

                    if (!$hasChanged) {
                        continue;
                    }

                    $this->addHistory($accountId, false, $account);

                    $this->accountRepository->updateBulk(
                        $accountId,
                        AccountModel::update($resolvedDto),
                        $changeOwner,
                        $changeUserGroup,
                        $updatePrivacy
                    );

                    $this->accountItemsService->updateItems($userCanChangePermissions, $accountId, $resolvedDto);

                    $this->revokeAccountAndChildPublicLinks($accountId);
                }
            },
            $this
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    private function hasBulkChanges(
        int $id,
        AccountModel $account,
        AccountUpdateDto $dto,
        bool $userCanChangePermissions,
        bool $changeOwner,
        bool $changeUserGroup,
        bool $updatePrivacy
    ): bool {
        if (!empty($dto->clientId) && (int)$dto->clientId !== (int)$account->getClientId()) {
            return true;
        }

        if (!empty($dto->categoryId) && (int)$dto->categoryId !== (int)$account->getCategoryId()) {
            return true;
        }

        if (!empty($dto->passDateChange) && (int)$dto->passDateChange !== (int)($account->getPassDateChange() ?? 0)) {
            return true;
        }

        if ($changeOwner && !empty($dto->userId) && (int)$dto->userId !== (int)$account->getUserId()) {
            return true;
        }

        if ($changeUserGroup && !empty($dto->userGroupId) && (int)$dto->userGroupId !== (int)$account->getUserGroupId()) {
            return true;
        }

        if ($updatePrivacy
            && ((bool)$dto->isPrivate !== (bool)$account->getIsPrivate()
                || (bool)$dto->isPrivateGroup !== (bool)$account->getIsPrivateGroup())
        ) {
            return true;
        }

        if ($this->hasTagsChanged($id, $dto->tags)) {
            return true;
        }

        if (!$userCanChangePermissions) {
            return false;
        }

        return $this->hasAccountGrantsChanged($id, $dto);
    }

    private function userCanChangeOwner(
        UserDto $userData,
        ProfileData $userProfile,
        AccountModel $account
    ): bool {
        return $userData->isAdminApp || $userData->isAdminAcc
               || ($userProfile->isAccPermission() && $userData->id === $account->getUserId());
    }

    private function userCanChangeGroup(
        UserDto $userDto,
        ProfileData $userProfile,
        AccountModel $account
    ): bool {
        return $this->userCanChangeOwner($userDto, $userProfile, $account)
               || ($userProfile->isAccPermission() && $userDto->userGroupId === $account->getUserGroupId());
    }

    /**
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws SPException
     */
    private function addHistory(int $accountId, bool $isDelete = false, ?AccountModel $account = null): int
    {
        $historyId = $this->accountHistoryService->create(
            new AccountHistoryCreateDto(
                $account ?? $this->accountReader->getById($accountId),
                !$isDelete,
                $isDelete,
                $this->configService->getByParam('masterPwd')
            )
        );

        if ($historyId > 0) {
            $this->customFieldDataService?->copyByModuleAndItem(
                AclActionsInterface::ACCOUNT,
                $accountId,
                AclActionsInterface::ACCOUNT_HISTORY_VIEW,
                $historyId
            );
        }

        return $historyId;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws AccountPermissionException
     */
    private function checkAccountAccess(int $accountId): void
    {
        if (!$this->accountReader->canEdit($accountId)) {
            throw new AccountPermissionException(SPException::INFO);
        }
    }

    /**
     * @throws ServiceException
     */
    public function create(AccountCreateDto $accountCreateDto): int
    {
        return $this->accountRepository->transactionAware(
            function () use ($accountCreateDto) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();

                $userCanChangePermissions = AccountAcl::getShowPermission($userData, $userProfile);

                if (!$userCanChangePermissions) {
                    $accountCreateDto = self::buildWithUserData($userData, $accountCreateDto);
                }

                if ($accountCreateDto->name !== null) {
                    $trimmedName = trim($accountCreateDto->name);
                    if ($trimmedName !== $accountCreateDto->name) {
                        $accountCreateDto = $accountCreateDto->mutate(['name' => $trimmedName]);
                    }
                }

                $ownerGroupId = $this->validateAccountFields(
                    $accountCreateDto->name,
                    $accountCreateDto->login,
                    $accountCreateDto->url,
                    $accountCreateDto->clientId,
                    $accountCreateDto->categoryId,
                    $accountCreateDto->userId,
                    $accountCreateDto->userGroupId,
                    $accountCreateDto->notes
                );

                $parentId = (int)($accountCreateDto->parentId ?? 0);
                $this->validateParentLink($parentId);

                [$accountCreateDto, $presetApplied] = $this->applyPresetPrivate($accountCreateDto);

                if (!$presetApplied) {
                    $this->assertPrivacyAuthorityForCreate(
                        $userData,
                        $userProfile,
                        (bool)$accountCreateDto->isPrivate,
                        (bool)$accountCreateDto->isPrivateGroup,
                        (int)$accountCreateDto->userId,
                        (int)$accountCreateDto->userGroupId
                    );
                }

                $this->assertPrivacyCompatibility(
                    (bool)$accountCreateDto->isPrivate,
                    (bool)$accountCreateDto->isPrivateGroup,
                    $ownerGroupId,
                    (int)$accountCreateDto->userGroupId
                );

                if ($parentId > 0 && (string)($accountCreateDto->pass ?? '') === '') {
                    $encryptedPassword = new EncryptedPassword('', '');
                } else {
                    $encryptedPassword = $this->accountCryptService->getPasswordEncrypted($accountCreateDto->pass);
                }

                $accountCreateDto = $accountCreateDto->withEncryptedPassword($encryptedPassword);

                $accountId = $this->accountRepository->create(AccountModel::create($accountCreateDto))->getLastId();

                if ($this->customFieldDataService !== null && !empty($accountCreateDto->customFields)) {
                    foreach ($accountCreateDto->customFields as $definitionId => $value) {
                        $stringValue = $value === null ? '' : (string)$value;
                        if ($stringValue !== '') {
                            $this->customFieldDataService->create(
                                new CustomFieldDataModel([
                                    'itemId' => $accountId,
                                    'moduleId' => AclActionsInterface::ACCOUNT,
                                    'definitionId' => (int)$definitionId,
                                    'data' => $stringValue,
                                ])
                            );
                        }
                    }
                }

                $this->accountItemsService->addItems($userCanChangePermissions, $accountId, $accountCreateDto);

                $this->accountPresetService->addPresetPermissions($accountId);

                return $accountId;
            },
            $this
        );
    }

    /**
     * @throws SPException
     */
    private static function buildWithUserData(
        UserDto $userData,
        AccountCreateDto $accountCreateDto
    ): AccountCreateDto {
        return $accountCreateDto->withUserGroupId($userData->userGroupId)->withUserId($userData->id);
    }

    /**
     * @template TDto of AccountCreateDto|AccountUpdateDto
     * @param TDto $accountDto
     * @return array{TDto, bool}
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws NoSuchItemException
     * @throws SPException
     */
    private function applyPresetPrivate(
        AccountCreateDto|AccountUpdateDto $accountDto,
        ?AccountModel $existingAccount = null
    ): array {
        $userDto = $this->context->getUserData();
        $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE);

        if ($itemPreset !== null && $itemPreset->getFixed()) {
            $accountPrivate = $itemPreset->hydrate(AccountPrivate::class);

            if ($accountDto instanceof AccountUpdateDto && null !== $existingAccount) {
                $accountDto = $accountDto
                    ->withUserId($existingAccount->getUserId())
                    ->withUserGroupId($existingAccount->getUserGroupId());
            }

            $privateUser = $userDto->id === $accountDto->userId
                           && $accountPrivate->isPrivateUser();
            $privateGroup = $userDto->userGroupId === $accountDto->userGroupId
                            && $accountPrivate->isPrivateGroup();

            return [$accountDto->withPrivate($privateUser)->withPrivateGroup($privateGroup), true];
        }

        return [$accountDto, false];
    }

    /**
     * @throws ValidationException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function validateAccountFields(
        ?string $name,
        ?string $login,
        ?string $url,
        ?int $clientId,
        ?int $categoryId,
        ?int $userId,
        ?int $userGroupId,
        ?string $notes = null
    ): ?int {
        $trimmedName = $name !== null ? trim($name) : null;
        if ($trimmedName === null || $trimmedName === '' || mb_strlen($trimmedName) > 100) {
            throw new ValidationException(__u('An account name needed'));
        }

        if ($login !== null && mb_strlen($login) > 50) {
            throw new ValidationException(__u('Invalid login'));
        }

        if ($url !== null && mb_strlen($url) > 255) {
            throw new ValidationException(__u('Invalid URL'));
        }

        if ($notes !== null && mb_strlen($notes) > 5000) {
            throw new ValidationException(__u('Notes too long'));
        }

        if ($clientId === null || $clientId <= 0) {
            throw new ValidationException(__u('A client is needed'));
        }

        if ($this->clientRepository !== null && $this->clientRepository->getById($clientId)->getNumRows() === 0) {
            throw new ValidationException(__u('A client is needed'));
        }

        if ($categoryId === null || $categoryId <= 0) {
            throw new ValidationException(__u('A category is needed'));
        }

        if ($this->categoryRepository !== null && $this->categoryRepository->getById($categoryId)->getNumRows() === 0) {
            throw new ValidationException(__u('A category is needed'));
        }

        if ($userId === null || $userId <= 0) {
            throw new ValidationException(__u('An owner is needed'));
        }

        $ownerGroupId = null;
        if ($this->userRepository !== null) {
            $userResult = $this->userRepository->getById($userId);
            if ($userResult->getNumRows() === 0) {
                throw new ValidationException(__u('An owner is needed'));
            }
            $ownerGroupId = $userResult->getData(UserModel::class)->getUserGroupId();
        } elseif ($userId === $this->context->getUserData()->id) {
            $ownerGroupId = $this->context->getUserData()->userGroupId;
        }

        if ($userGroupId === null || $userGroupId <= 0) {
            throw new ValidationException(__u('A user group is needed'));
        }

        if ($this->userGroupRepository !== null
            && $this->userGroupRepository->getById($userGroupId)->getNumRows() === 0
        ) {
            throw new ValidationException(__u('A user group is needed'));
        }

        return $ownerGroupId;
    }

    /**
     * @throws AccountPermissionException
     */
    private function assertPrivacyAuthorityForCreate(
        UserDto $userData,
        ProfileData $userProfile,
        bool $isPrivate,
        bool $isPrivateGroup,
        int $targetUserId,
        int $targetUserGroupId
    ): void {
        if ($isPrivate && !($userData->isAdminApp || ($userProfile->isAccPrivate() && $userData->id === $targetUserId))) {
            throw new AccountPermissionException(SPException::INFO);
        }

        if ($isPrivateGroup
            && !($userData->isAdminApp || ($userProfile->isAccPrivateGroup() && $userData->userGroupId === $targetUserGroupId))
        ) {
            throw new AccountPermissionException(SPException::INFO);
        }
    }

    /**
     * @throws AccountPermissionException
     */
    private function assertPrivacyAuthorityForUpdate(
        UserDto $userData,
        ProfileData $userProfile,
        AccountModel $account,
        bool $requestedPrivate,
        bool $requestedPrivateGroup
    ): void {
        if ($requestedPrivate !== (bool)$account->getIsPrivate()
            && !($userData->isAdminApp || ($userProfile->isAccPrivate() && $userData->id === $account->getUserId()))
        ) {
            throw new AccountPermissionException(SPException::INFO);
        }

        if ($requestedPrivateGroup !== (bool)$account->getIsPrivateGroup()
            && !(
                $userData->isAdminApp
                || ($userProfile->isAccPrivateGroup() && $userData->userGroupId === $account->getUserGroupId())
            )
        ) {
            throw new AccountPermissionException(SPException::INFO);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertPrivacyCompatibility(
        bool $isPrivate,
        bool $isPrivateGroup,
        ?int $ownerGroupId,
        int $effectiveUserGroupId
    ): void {
        if ($isPrivate && $isPrivateGroup && $ownerGroupId !== null && $ownerGroupId !== $effectiveUserGroupId) {
            throw new ValidationException(__u('Invalid privacy configuration'));
        }
    }

    /**
     * Updates external items for the account
     *
     * @throws ServiceException
     */
    public function update(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->checkAccountAccess($id);

        $this->accountRepository->transactionAware(
            function () use ($id, $accountUpdateDto) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();
                $account = $this->accountReader->getById($id);

                $userCanChangePermissions = AccountAcl::getShowPermission($userData, $userProfile);
                $changeOwner = $userCanChangePermissions && $this->userCanChangeOwner($userData, $userProfile, $account);
                $changeUserGroup = $userCanChangePermissions && $this->userCanChangeGroup($userData, $userProfile, $account);

                $requestedPrivate = $accountUpdateDto->isPrivate ?? (bool)$account->getIsPrivate();
                $requestedPrivateGroup = $accountUpdateDto->isPrivateGroup ?? (bool)$account->getIsPrivateGroup();

                $merged = [];
                if ($accountUpdateDto->name === null) {
                    $merged['name'] = $account->getName();
                }
                if ($accountUpdateDto->clientId === null) {
                    $merged['clientId'] = $account->getClientId();
                }
                if ($accountUpdateDto->categoryId === null) {
                    $merged['categoryId'] = $account->getCategoryId();
                }
                if ($accountUpdateDto->login === null) {
                    $merged['login'] = $account->getLogin();
                }
                if ($accountUpdateDto->url === null) {
                    $merged['url'] = $account->getUrl();
                }
                if ($accountUpdateDto->notes === null) {
                    $merged['notes'] = $account->getNotes();
                }
                if ($accountUpdateDto->passDateChange === null) {
                    $merged['passDateChange'] = $account->getPassDateChange();
                }
                if ($accountUpdateDto->parentId === null) {
                    $merged['parentId'] = $account->getParentId();
                }
                if ($accountUpdateDto->isPrivate === null) {
                    $merged['isPrivate'] = (bool)$account->getIsPrivate();
                }
                if ($accountUpdateDto->isPrivateGroup === null) {
                    $merged['isPrivateGroup'] = (bool)$account->getIsPrivateGroup();
                }
                if ($accountUpdateDto->otherUserEdit === null) {
                    $merged['otherUserEdit'] = (bool)$account->getOtherUserEdit();
                }
                if ($accountUpdateDto->otherUserGroupEdit === null) {
                    $merged['otherUserGroupEdit'] = (bool)$account->getOtherUserGroupEdit();
                }
                if ($accountUpdateDto->userId === null) {
                    $merged['userId'] = $account->getUserId();
                }
                if ($accountUpdateDto->userGroupId === null) {
                    $merged['userGroupId'] = $account->getUserGroupId();
                }
                if ($accountUpdateDto->userEditId === null) {
                    $merged['userEditId'] = $userData->id;
                }

                $resolvedDto = $merged !== [] ? $accountUpdateDto->mutate($merged) : $accountUpdateDto;

                if ($resolvedDto->name !== null) {
                    $trimmedName = trim($resolvedDto->name);
                    if ($trimmedName !== $resolvedDto->name) {
                        $resolvedDto = $resolvedDto->mutate(['name' => $trimmedName]);
                    }
                }

                $effectiveUserId = $changeOwner ? (int)$resolvedDto->userId : (int)$account->getUserId();
                $effectiveUserGroupId = $changeUserGroup ? (int)$resolvedDto->userGroupId : (int)$account->getUserGroupId();

                $ownerGroupId = $this->validateAccountFields(
                    $resolvedDto->name,
                    $resolvedDto->login,
                    $resolvedDto->url,
                    $resolvedDto->clientId,
                    $resolvedDto->categoryId,
                    $effectiveUserId,
                    $effectiveUserGroupId,
                    $resolvedDto->notes
                );

                [$resolvedDto, $presetApplied] = $this->applyPresetPrivate($resolvedDto, $account);

                if (!$presetApplied) {
                    $this->assertPrivacyAuthorityForUpdate(
                        $userData,
                        $userProfile,
                        $account,
                        $requestedPrivate,
                        $requestedPrivateGroup
                    );
                }

                $presetUserId = $presetApplied ? (int)$resolvedDto->userId : $effectiveUserId;
                $presetUserGroupId = $presetApplied ? (int)$resolvedDto->userGroupId : $effectiveUserGroupId;

                if ($presetApplied && $presetUserId !== (int)$account->getUserId()) {
                    $ownerGroupId = $this->validateAccountFields(
                        $resolvedDto->name,
                        $resolvedDto->login,
                        $resolvedDto->url,
                        $resolvedDto->clientId,
                        $resolvedDto->categoryId,
                        $presetUserId,
                        $presetUserGroupId,
                        $resolvedDto->notes
                    );
                }

                $this->assertPrivacyCompatibility(
                    (bool)$resolvedDto->isPrivate,
                    (bool)$resolvedDto->isPrivateGroup,
                    $ownerGroupId,
                    $presetUserGroupId
                );

                $oldParentId = (int)($account->getParentId() ?? 0);
                $newParentId = (int)($resolvedDto->parentId ?? 0);

                if ($newParentId > 0 && ($newParentId !== $oldParentId || $newParentId === $id)) {
                    $this->validateParentLink($newParentId, $id);
                }

                $unlinkingWithNewPass = false;
                if ($oldParentId > 0 && $newParentId === 0) {
                    if ($this->acl !== null && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_EDIT_PASS)) {
                        throw new AccountPermissionException(SPException::INFO);
                    }

                    if ($this->acl !== null && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS)) {
                        throw new AccountPermissionException(SPException::INFO);
                    }

                    if (!$this->accountReader->isVisible($oldParentId)) {
                        throw new AccountPermissionException(SPException::INFO);
                    }

                    if ((string)($resolvedDto->pass ?? '') !== '') {
                        $resolvedDto = $this->accountPresetService->checkPasswordPreset($resolvedDto);
                        $unlinkingWithNewPass = true;
                    } else {
                        $existingPass = (string)($account->getPass() ?? '');
                        $existingKey = (string)($account->getKey() ?? '');

                        if ($existingPass === '' || $existingKey === '') {
                            throw new ValidationException(__u('A key is needed'));
                        }

                        if ($this->crypt !== null && $this->masterKeyStore !== null) {
                            try {
                                $this->crypt->decrypt($existingPass, $existingKey, $this->masterKeyStore->get());
                            } catch (SPException) {
                                throw new ValidationException(__u('A key is needed'));
                            }
                        }
                    }
                }

                $scalarContentChanged = $this->hasScalarContentChanged($account, $resolvedDto);
                $tagsChanged = $this->hasTagsChanged($id, $resolvedDto->tags);
                $customFieldsChanged = $this->hasCustomFieldsChanged($id, $resolvedDto->customFields);
                $permissionsOrPrivacyChanged = $this->hasPermissionsOrPrivacyChanged(
                    $id,
                    $account,
                    $resolvedDto,
                    $userCanChangePermissions,
                    $changeOwner,
                    $changeUserGroup
                );

                if (!$scalarContentChanged
                    && !$tagsChanged
                    && !$customFieldsChanged
                    && !$permissionsOrPrivacyChanged
                ) {
                    return;
                }

                $this->addHistory($id, false, $account);

                $this->accountRepository->update(
                    $id,
                    AccountModel::update($resolvedDto),
                    $changeOwner,
                    $changeUserGroup
                );

                if ($unlinkingWithNewPass) {
                    $encryptedPassword = $this->accountCryptService->getPasswordEncrypted((string)$resolvedDto->pass);
                    $this->accountRepository->editPassword(
                        $id,
                        AccountModel::updatePassword(
                            $resolvedDto->mutate([
                                'pass' => $encryptedPassword->getPass(),
                                'key' => $encryptedPassword->getKey(),
                                'userEditId' => (int)($resolvedDto->userEditId ?? $userData->id),
                            ])
                        )
                    );
                }

                $this->saveCustomFieldsForUpdate($id, $resolvedDto->customFields);

                $this->accountItemsService->updateItems($userCanChangePermissions, $id, $resolvedDto);

                $this->accountPresetService->addPresetPermissions($id);

                if ($scalarContentChanged || $tagsChanged || $customFieldsChanged) {
                    $this->revokeAccountAndChildPublicLinks($id);
                }
            },
            $this
        );
    }

    private function hasScalarContentChanged(AccountModel $account, AccountUpdateDto $dto): bool
    {
        return (string)$account->getName() !== (string)$dto->name
            || (string)($account->getLogin() ?? '') !== (string)($dto->login ?? '')
            || (string)($account->getUrl() ?? '') !== (string)($dto->url ?? '')
            || (string)($account->getNotes() ?? '') !== (string)($dto->notes ?? '')
            || (int)$account->getClientId() !== (int)$dto->clientId
            || (int)$account->getCategoryId() !== (int)$dto->categoryId
            || (int)($account->getPassDateChange() ?? 0) !== (int)($dto->passDateChange ?? 0)
            || (int)($account->getParentId() ?? 0) !== (int)($dto->parentId ?? 0);
    }

    /**
     * @param int[]|null $tags
     * @throws ConstraintException
     * @throws QueryException
     */
    private function hasTagsChanged(int $id, ?array $tags): bool
    {
        if ($tags === null) {
            return false;
        }

        if ($this->accountToTagRepository === null) {
            return true;
        }

        $existing = array_map(
            static fn($tag) => $tag instanceof Item ? (int)$tag->getId() : (int)($tag['id'] ?? 0),
            $this->accountToTagRepository->getTagsByAccountId($id)->getDataAsArray()
        );
        $incoming = array_map('intval', $tags);

        sort($existing);
        sort($incoming);

        return array_values(array_unique($existing)) !== array_values(array_unique($incoming));
    }

    /**
     * @param array<int|string, mixed>|null $customFields
     * @throws ServiceException
     */
    private function hasCustomFieldsChanged(int $id, ?array $customFields): bool
    {
        if ($customFields === null) {
            return false;
        }

        if ($this->customFieldDataService === null) {
            return $customFields !== [];
        }

        $existing = [];
        foreach ($this->customFieldDataService->getBy(AclActionsInterface::ACCOUNT, $id) as $row) {
            $definitionId = (int)($row['definitionId'] ?? 0);
            if ($definitionId <= 0) {
                continue;
            }

            $data = (string)($row['data'] ?? '');
            $key = (string)($row['key'] ?? '');

            if ($data !== '' && $key !== '') {
                $existing[$definitionId] = (string)($this->customFieldDataService->decrypt($data, $key) ?? '');
            } else {
                $existing[$definitionId] = $data;
            }
        }

        foreach ($customFields as $definitionId => $value) {
            $defId = (int)$definitionId;
            $newValue = $value === null ? '' : (string)$value;
            $oldValue = $existing[$defId] ?? '';

            if ($newValue !== $oldValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    private function hasPermissionsOrPrivacyChanged(
        int $id,
        AccountModel $account,
        AccountUpdateDto $dto,
        bool $userCanChangePermissions,
        bool $changeOwner,
        bool $changeUserGroup
    ): bool {
        if ((bool)$account->getIsPrivate() !== (bool)$dto->isPrivate
            || (bool)$account->getIsPrivateGroup() !== (bool)$dto->isPrivateGroup
            || (bool)$account->getOtherUserEdit() !== (bool)$dto->otherUserEdit
            || (bool)$account->getOtherUserGroupEdit() !== (bool)$dto->otherUserGroupEdit
        ) {
            return true;
        }

        if ($changeOwner && (int)$account->getUserId() !== (int)$dto->userId) {
            return true;
        }

        if ($changeUserGroup && (int)$account->getUserGroupId() !== (int)$dto->userGroupId) {
            return true;
        }

        if (!$userCanChangePermissions) {
            return false;
        }

        return $this->hasAccountGrantsChanged($id, $dto);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    private function hasAccountGrantsChanged(int $id, AccountUpdateDto $dto): bool
    {
        if ($dto->usersView !== null || $dto->usersEdit !== null) {
            if ($this->accountToUserRepository === null) {
                return true;
            }

            $existingView = [];
            $existingEdit = [];
            foreach ($this->accountToUserRepository->getUsersByAccountId($id)->getDataAsArray() as $item) {
                /** @var AccountItem $item */
                $itemId = (int)$item->getId();
                if ((int)$item->getIsEdit() === 1) {
                    $existingEdit[] = $itemId;
                } else {
                    $existingView[] = $itemId;
                }
            }

            if ($dto->usersView !== null && $this->normalizeIntList($existingView) !== $this->normalizeIntList($dto->usersView)) {
                return true;
            }
            if ($dto->usersEdit !== null && $this->normalizeIntList($existingEdit) !== $this->normalizeIntList($dto->usersEdit)) {
                return true;
            }
        }

        if ($dto->userGroupsView !== null || $dto->userGroupsEdit !== null) {
            if ($this->accountToUserGroupRepository === null) {
                return true;
            }

            $existingView = [];
            $existingEdit = [];
            foreach ($this->accountToUserGroupRepository->getUserGroupsByAccountId($id)->getDataAsArray() as $item) {
                /** @var AccountItem $item */
                $itemId = (int)$item->getId();
                if ((int)$item->getIsEdit() === 1) {
                    $existingEdit[] = $itemId;
                } else {
                    $existingView[] = $itemId;
                }
            }

            if ($dto->userGroupsView !== null
                && $this->normalizeIntList($existingView) !== $this->normalizeIntList($dto->userGroupsView)
            ) {
                return true;
            }
            if ($dto->userGroupsEdit !== null
                && $this->normalizeIntList($existingEdit) !== $this->normalizeIntList($dto->userGroupsEdit)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int|string> $values
     * @return int[]
     */
    private function normalizeIntList(array $values): array
    {
        $ints = array_values(array_unique(array_map('intval', $values)));
        sort($ints);

        return $ints;
    }

    /**
     * @param array<int|string, mixed>|null $customFields
     * @throws ServiceException
     */
    private function saveCustomFieldsForUpdate(int $id, ?array $customFields): void
    {
        if ($this->customFieldDataService === null || empty($customFields)) {
            return;
        }

        foreach ($customFields as $definitionId => $value) {
            $stringValue = $value === null ? '' : (string)$value;
            if ($stringValue === '') {
                $this->customFieldDataService->deleteByDefinition(
                    $id,
                    AclActionsInterface::ACCOUNT,
                    (int)$definitionId
                );
            } else {
                $this->customFieldDataService->updateOrCreate(
                    new CustomFieldDataModel([
                        'itemId' => $id,
                        'moduleId' => AclActionsInterface::ACCOUNT,
                        'definitionId' => (int)$definitionId,
                        'data' => $stringValue,
                    ])
                );
            }
        }
    }

    /**
     * @throws ServiceException
     */
    public function editPassword(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->checkAccountAccess($id);

        $this->accountRepository->transactionAware(
            function () use ($id, $accountUpdateDto) {
                $this->addHistory($id);

                $encryptedPassword = $this->accountCryptService->getPasswordEncrypted($accountUpdateDto->pass);
                $userEditId = $accountUpdateDto->userEditId ?? $this->context->getUserData()->id;

                $this->accountRepository->editPassword(
                    $id,
                    AccountModel::updatePassword(
                        $accountUpdateDto->mutate([
                            'pass' => $encryptedPassword->getPass(),
                            'key' => $encryptedPassword->getKey(),
                            'userEditId' => $userEditId,
                        ])
                    )
                );

                $this->revokeAccountAndChildPublicLinks($id);
            },
            $this
        );
    }

    /**
     * Updates an already encrypted password data from a master password changing action
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function updatePasswordMasterPass(int $id, EncryptedPassword $encryptedPassword): void
    {
        $result = $this->accountRepository->updatePassword($id, $encryptedPassword);

        if ($result->getAffectedNumRows() === 0) {
            throw new ServiceException('Error while updating the password');
        }
    }

    /**
     * @throws ServiceException
     */
    public function restoreModified(AccountHistoryDto $dto): void
    {
        if ($dto->accountId === null || $dto->accountId <= 0) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $this->checkAccountAccess($dto->accountId);

        if ($this->clientRepository !== null
            && ($dto->clientId === null || $dto->clientId <= 0 || $this->clientRepository->getById($dto->clientId)->getNumRows() === 0)
        ) {
            throw new ValidationException(__u('A client is needed'));
        }

        if ($this->categoryRepository !== null
            && ($dto->categoryId === null || $dto->categoryId <= 0 || $this->categoryRepository->getById($dto->categoryId)->getNumRows() === 0)
        ) {
            throw new ValidationException(__u('A category is needed'));
        }

        $this->accountRepository->transactionAware(
            function () use ($dto) {
                $liveAccount = $this->accountReader->getById($dto->accountId);
                $this->addHistory($dto->accountId, false, $liveAccount);

                $preservePass = $this->acl !== null
                    && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_EDIT_PASS);

                $effectiveDto = $dto->mutate([
                    'userId' => $liveAccount->getUserId(),
                    'userGroupId' => $liveAccount->getUserGroupId(),
                    'isPrivate' => (bool)$liveAccount->getIsPrivate(),
                    'isPrivateGroup' => (bool)$liveAccount->getIsPrivateGroup(),
                    'otherUserEdit' => (bool)$liveAccount->getOtherUserEdit(),
                    'otherUserGroupEdit' => (bool)$liveAccount->getOtherUserGroupEdit(),
                    'pass' => $preservePass ? ($liveAccount->getPass() ?? '') : ($dto->pass ?? ''),
                    'key' => $preservePass ? ($liveAccount->getKey() ?? '') : ($dto->key ?? ''),
                    'parentId' => $preservePass
                        ? (int)($liveAccount->getParentId() ?? 0)
                        : (int)($dto->parentId ?? 0),
                ]);

                $result = $this->accountRepository->restoreModified(
                    $dto->accountId,
                    AccountModel::restoreModified($effectiveDto, $this->context->getUserData()->id)
                );

                if ($result->getAffectedNumRows() === 0) {
                    throw new ServiceException('Error on restoring the account');
                }

                if ($this->customFieldDataService !== null && ($dto->id ?? 0) > 0) {
                    $this->customFieldDataService->deleteByItem($dto->accountId, AclActionsInterface::ACCOUNT);
                    $this->customFieldDataService->copyByModuleAndItem(
                        AclActionsInterface::ACCOUNT_HISTORY_VIEW,
                        (int)$dto->id,
                        AclActionsInterface::ACCOUNT,
                        $dto->accountId
                    );
                }

                $this->revokeAccountAndChildPublicLinks($dto->accountId);
            },
            $this
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function restoreRemoved(AccountHistoryDto $accountHistoryDto): void
    {
        if ($accountHistoryDto->isDeleted !== true) {
            throw new ServiceException('Error on restoring the account');
        }

        if ($this->clientRepository === null) {
            $result = $this->accountRepository->createRemoved(
                AccountModel::restoreRemoved($accountHistoryDto, $this->context->getUserData()->id)
            );

            if ($result->getAffectedNumRows() === 0) {
                throw new ServiceException('Error on restoring the account');
            }

            return;
        }

        $oldId = (int)($accountHistoryDto->accountId ?? 0);
        $historyId = (int)($accountHistoryDto->id ?? 0);

        if ($oldId <= 0 || $historyId <= 0) {
            throw new AccountPermissionException(SPException::INFO);
        }

        /** @var AccountHistoryModel[] $allRows */
        $allRows = $this->accountHistoryService->getByAccountId($oldId);
        $deletionSnapshots = array_values(array_filter(
            $allRows,
            static fn(AccountHistoryModel $row): bool => (int)$row->getIsDeleted() === 1
        ));

        if (count($deletionSnapshots) !== 1 || (int)$deletionSnapshots[0]->getId() !== $historyId) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $snap = $deletionSnapshots[0];
        $isValidSnap = (int)($snap->getParentId() ?? 0) > 0
            || ((string)($snap->getPass() ?? '') !== '' && (string)($snap->getKey() ?? '') !== '');

        if (!$isValidSnap) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $this->assertCanRestoreDeletedSnapshot($snap);

        if ($accountHistoryDto->clientId === null
            || $accountHistoryDto->clientId <= 0
            || $this->clientRepository->getById($accountHistoryDto->clientId)->getNumRows() === 0
        ) {
            throw new ValidationException(__u('A client is needed'));
        }

        if ($accountHistoryDto->categoryId === null
            || $accountHistoryDto->categoryId <= 0
            || ($this->categoryRepository !== null && $this->categoryRepository->getById($accountHistoryDto->categoryId)->getNumRows() === 0)
        ) {
            throw new ValidationException(__u('A category is needed'));
        }

        if ($accountHistoryDto->userId === null
            || $accountHistoryDto->userId <= 0
            || ($this->userRepository !== null && $this->userRepository->getById($accountHistoryDto->userId)->getNumRows() === 0)
        ) {
            throw new ValidationException(__u('An owner is needed'));
        }

        if ($accountHistoryDto->userGroupId === null
            || $accountHistoryDto->userGroupId <= 0
            || ($this->userGroupRepository !== null && $this->userGroupRepository->getById($accountHistoryDto->userGroupId)->getNumRows() === 0)
        ) {
            throw new ValidationException(__u('A user group is needed'));
        }

        $this->accountRepository->transactionAware(
            function () use ($accountHistoryDto, $oldId, $historyId) {
                $occupyingRes = $this->accountRepository->getById($oldId);
                $preserveId = $occupyingRes->getNumRows() === 0;

                if (!$preserveId) {
                    /** @var AccountModel $occupyingAccount */
                    $occupyingAccount = $occupyingRes->getData(AccountModel::class);
                    $occupyingDateAdd = (string)($occupyingAccount->getDateAdd() ?? '');
                    $histDateAdd = (string)($accountHistoryDto->dateAdd ?? '');

                    if ($occupyingDateAdd === '' || $histDateAdd === '' || $occupyingDateAdd === $histDateAdd) {
                        throw new ServiceException('Error on restoring the account');
                    }
                }

                $result = $this->accountRepository->createRemoved(
                    AccountModel::restoreRemoved($accountHistoryDto, $this->context->getUserData()->id, $preserveId)
                );

                if ($result->getAffectedNumRows() === 0) {
                    throw new ServiceException('Error on restoring the account');
                }

                $restoredId = $preserveId ? $oldId : $result->getLastId();

                if ($restoredId <= 0) {
                    throw new ServiceException('Error on restoring the account');
                }

                if (!$preserveId) {
                    $this->accountHistoryService->reassignAccountId($oldId, $restoredId, (string)$accountHistoryDto->dateAdd);
                }

                if ($this->customFieldDataService !== null) {
                    $this->customFieldDataService->copyByModuleAndItem(
                        AclActionsInterface::ACCOUNT_HISTORY_VIEW,
                        $historyId,
                        AclActionsInterface::ACCOUNT,
                        $restoredId
                    );
                }
            },
            $this
        );
    }

    /**
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function assertCanRestoreDeletedSnapshot(AccountHistoryModel $snap): void
    {
        $userData = $this->context->getUserData();

        if ((bool)$snap->getIsPrivate() && (int)$snap->getUserId() !== $userData->id) {
            throw new AccountPermissionException(SPException::INFO);
        }

        if ((bool)$snap->getIsPrivateGroup() && (int)$snap->getUserGroupId() !== $userData->userGroupId) {
            throw new AccountPermissionException(SPException::INFO);
        }

        if ($userData->isAdminApp || $userData->isAdminAcc) {
            return;
        }

        if ((int)$snap->getUserId() === $userData->id || (int)$snap->getUserGroupId() === $userData->userGroupId) {
            return;
        }

        if ($this->userToUserGroupRepository !== null) {
            $rows = $this->userToUserGroupRepository->getGroupsForUser($userData->id)->getDataAsArray();
            $groupIds = array_map(
                static fn($row): int => (int)($row->getUserGroupId() ?? 0),
                $rows
            );

            if (in_array((int)$snap->getUserGroupId(), $groupIds, true)) {
                return;
            }
        }

        throw new AccountPermissionException(SPException::INFO);
    }

    /**
     * @throws ServiceException
     */
    public function delete(int $id): void
    {
        $this->checkAccountAccess($id);

        if ($this->accountRepository->getChildIdsByParentId($id) !== []) {
            throw new ServiceException(__u('Error while deleting the account'));
        }

        $this->accountRepository->transactionAware(
            function () use ($id) {
                $this->addHistory($id, true);

                if ($this->accountRepository->delete($id)->getAffectedNumRows() === 0) {
                    throw new NoSuchItemException('Account not found');
                }

                $this->customFieldDataService?->deleteByItem($id, AclActionsInterface::ACCOUNT);
                $this->revokeAccountAndChildPublicLinks($id);
            },
            $this
        );
    }

    /**
     * @param int[] $ids
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void
    {
        $uniqueIds = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if ($uniqueIds === []) {
            throw ServiceException::error('Error while deleting the accounts');
        }

        foreach ($uniqueIds as $id) {
            $this->checkAccountAccess($id);

            if (array_diff($this->accountRepository->getChildIdsByParentId($id), $uniqueIds) !== []) {
                throw new ServiceException(__u('Error while deleting the account'));
            }
        }

        $this->accountRepository->transactionAware(function () use ($uniqueIds) {
            if ($this->publicLinkRepository !== null || $this->customFieldDataService !== null) {
                foreach ($uniqueIds as $id) {
                    $account = $this->accountReader->getById($id);
                    $this->addHistory($id, true, $account);
                }

                $this->customFieldDataService?->delete($uniqueIds, AclActionsInterface::ACCOUNT);
                $this->publicLinkRepository?->deleteByItemIds($uniqueIds);

                $affectedNumRows = $this->accountRepository->deleteByIdBatch($uniqueIds)->getAffectedNumRows();

                if ($affectedNumRows !== count($uniqueIds)) {
                    throw ServiceException::error('Error while deleting the accounts');
                }

                return;
            }

            $affectedNumRows = $this->accountRepository->deleteByIdBatch($uniqueIds)->getAffectedNumRows();

            if ($affectedNumRows === 0) {
                throw ServiceException::error('Error while deleting the accounts');
            }
        }, $this);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ValidationException
     * @throws AccountPermissionException
     */
    private function validateParentLink(int $parentId, ?int $accountId = null): void
    {
        if ($parentId <= 0) {
            return;
        }

        if ($accountId !== null && $parentId === $accountId) {
            throw new ValidationException(__u('Invalid parent account'));
        }

        if ($accountId !== null && count($this->accountRepository->getChildIdsByParentId($accountId)) > 0) {
            throw new ValidationException(__u('Invalid parent account'));
        }

        if ($this->acl !== null && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS)) {
            throw new AccountPermissionException(SPException::INFO);
        }

        if (!$this->accountReader->isVisible($parentId)) {
            throw new AccountPermissionException(SPException::INFO);
        }

        try {
            $parent = $this->accountReader->getById($parentId);
        } catch (NoSuchItemException) {
            throw new ValidationException(__u('Invalid parent account'));
        }

        if ((int)($parent->getParentId() ?? 0) > 0) {
            throw new ValidationException(__u('Invalid parent account'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    private function revokeAccountAndChildPublicLinks(int $accountId): void
    {
        if ($this->publicLinkRepository === null) {
            return;
        }

        $itemIds = array_merge([$accountId], $this->accountRepository->getChildIdsByParentId($accountId));
        $this->publicLinkRepository->deleteByItemIds($itemIds);
    }
}
