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
use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountItemsService;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\ItemPreset\Models\AccountPrivate;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;

/**
 * Applies account mutations, history and preset rules.
 */
final class AccountWriter extends Service
{
    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly AccountRepository $accountRepository,
        private readonly ItemPresetService $itemPresetService,
        private readonly AccountHistoryService $accountHistoryService,
        private readonly AccountItemsService $accountItemsService,
        private readonly AccountPresetService $accountPresetService,
        private readonly ConfigService $configService,
        private readonly AccountCryptService $accountCryptService,
        private readonly AccountReader $accountReader
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * @throws ServiceException
     */
    public function updateBulk(AccountUpdateBulkDto $accountUpdateBulkDto): void
    {
        $this->accountRepository->transactionAware(
            function () use ($accountUpdateBulkDto) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();

                $userCanChangePermissions = AccountAcl::getShowPermission($userData, $userProfile);

                foreach ($accountUpdateBulkDto->getAccountUpdateDto() as $accountId => $accountUpdateDto) {
                    $changeOwner = false;
                    $changeUserGroup = false;

                    if ($userCanChangePermissions) {
                        $account = $this->accountReader->getById($accountId);

                        $changeOwner = $this->userCanChangeOwner($userData, $userProfile, $account);
                        $changeUserGroup = $this->userCanChangeGroup($userData, $userProfile, $account);
                    }

                    $this->addHistory($accountId);

                    $this->accountRepository->updateBulk(
                        $accountId,
                        AccountModel::update($accountUpdateDto),
                        $changeOwner,
                        $changeUserGroup
                    );

                    $this->accountItemsService->updateItems($userCanChangePermissions, $accountId, $accountUpdateDto);
                }
            },
            $this
        );
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
    private function addHistory(int $accountId, bool $isDelete = false): void
    {
        $this->accountHistoryService->create(
            new AccountHistoryCreateDto(
                $this->accountReader->getById($accountId),
                !$isDelete,
                $isDelete,
                $this->configService->getByParam('masterPwd')
            )
        );
    }

    /**
     * @throws ServiceException
     */
    public function create(AccountCreateDto $accountCreateDto): int
    {
        return $this->accountRepository->transactionAware(
            function () use ($accountCreateDto) {
                $userData = $this->context->getUserData();

                $userCanChangePermissions =
                    AccountAcl::getShowPermission($userData, $this->context->getUserProfile());

                if (!$userCanChangePermissions) {
                    $accountCreateDto = self::buildWithUserData($userData, $accountCreateDto);
                }

                $accountCreateDto = $accountCreateDto->withEncryptedPassword(
                    $this->accountCryptService->getPasswordEncrypted($accountCreateDto->pass)
                );

                $accountCreateDto = $this->setPresetPrivate($accountCreateDto);

                $accountId = $this->accountRepository->create(AccountModel::create($accountCreateDto))->getLastId();

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
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws NoSuchItemException
     * @throws SPException
     */
    private function setPresetPrivate(
        AccountCreateDto|AccountUpdateDto $accountDto,
        ?int $accountId = null
    ): AccountCreateDto|AccountUpdateDto {
        $userDto = $this->context->getUserData();
        $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE);

        if ($itemPreset !== null && $itemPreset->getFixed()) {
            $accountPrivate = $itemPreset->hydrate(AccountPrivate::class);

            if ($accountDto instanceof AccountUpdateDto && null !== $accountId) {
                $account = $this->accountReader->getById($accountId);
                $accountDto =
                    $accountDto->withUserId($account->getUserId())->withUserGroupId($account->getUserGroupId());
            }

            $privateUser = $userDto->id === $accountDto->userId
                           && $accountPrivate->isPrivateUser();
            $privateGroup = $userDto->userGroupId === $accountDto->userGroupId
                            && $accountPrivate->isPrivateGroup();

            return $accountDto->withPrivate($privateUser)->withPrivateGroup($privateGroup);
        }

        return $accountDto;
    }

    /**
     * Updates external items for the account
     *
     * @throws ServiceException
     */
    public function update(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->accountRepository->transactionAware(
            function () use ($id, $accountUpdateDto) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();

                $userCanChangePermissions = AccountAcl::getShowPermission($userData, $userProfile);

                if ($userCanChangePermissions) {
                    $account = $this->accountReader->getById($id);

                    $changeOwner = $this->userCanChangeOwner($userData, $userProfile, $account);
                    $changeUserGroup = $this->userCanChangeGroup($userData, $userProfile, $account);
                } else {
                    $changeOwner = false;
                    $changeUserGroup = false;
                }

                $this->addHistory($id);

                $accountUpdateDto = $this->setPresetPrivate($accountUpdateDto, $id);

                $this->accountRepository->update(
                    $id,
                    AccountModel::update($accountUpdateDto),
                    $changeOwner,
                    $changeUserGroup
                );

                $this->accountItemsService->updateItems($userCanChangePermissions, $id, $accountUpdateDto);

                $this->accountPresetService->addPresetPermissions($id);
            },
            $this
        );
    }

    /**
     * @throws ServiceException
     */
    public function editPassword(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->accountRepository->transactionAware(
            function () use ($id, $accountUpdateDto) {
                $this->addHistory($id);

                $encryptedPassword = $this->accountCryptService->getPasswordEncrypted($accountUpdateDto->pass);

                $this->accountRepository->editPassword(
                    $id,
                    AccountModel::updatePassword($accountUpdateDto->withEncryptedPassword($encryptedPassword))
                );
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
        $this->accountRepository->transactionAware(
            function () use ($dto) {
                $this->addHistory($dto->accountId);

                $result = $this->accountRepository->restoreModified(
                    $dto->accountId,
                    AccountModel::restoreModified($dto, $this->context->getUserData()->id)
                );

                if ($result->getAffectedNumRows() === 0) {
                    throw new ServiceException('Error on restoring the account');
                }
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
        $result = $this->accountRepository->createRemoved(
            AccountModel::restoreRemoved($accountHistoryDto, $this->context->getUserData()->id)
        );

        if ($result->getAffectedNumRows() === 0) {
            throw new ServiceException('Error on restoring the account');
        }
    }

    /**
     * @throws ServiceException
     */
    public function delete(int $id): void
    {
        $this->accountRepository->transactionAware(
            function () use ($id) {
                $this->addHistory($id, true);

                if ($this->accountRepository->delete($id)->getAffectedNumRows() === 0) {
                    throw new NoSuchItemException('Account not found');
                }
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
        $this->accountRepository->transactionAware(function () use ($ids) {
            $affectedNumRows = $this->accountRepository->deleteByIdBatch($ids)->getAffectedNumRows();

            if ($affectedNumRows === 0) {
                throw ServiceException::error('Error while deleting the accounts');
            }
        }, $this);
    }
}
