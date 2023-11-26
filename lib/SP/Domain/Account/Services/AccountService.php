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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\Domain\Account\Adapters\AccountData;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountDataView;
use SP\Domain\Account\Ports\AccountCryptServiceInterface;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Account\Ports\AccountItemsServiceInterface;
use SP\Domain\Account\Ports\AccountPresetServiceInterface;
use SP\Domain\Account\Ports\AccountRepositoryInterface;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Domain\Account\Ports\AccountToTagRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigServiceInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetServiceInterface;
use SP\Domain\User\Services\UserLoginResponse;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AccountService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountService extends Service implements AccountServiceInterface
{
    public function __construct(
        Application $application,
        private AccountRepositoryInterface $accountRepository,
        private AccountToUserGroupRepositoryInterface $accountToUserGroupRepository,
        private AccountToUserRepositoryInterface $accountToUserRepository,
        private AccountToTagRepositoryInterface $accountToTagRepository,
        private ItemPresetServiceInterface $itemPresetService,
        private AccountHistoryServiceInterface $accountHistoryService,
        private AccountItemsServiceInterface $accountItemsService,
        private AccountPresetServiceInterface $accountPresetService,
        private ConfigServiceInterface $configService,
        private AccountCryptServiceInterface $accountCryptService
    ) {
        parent::__construct($application);
    }

    /**
     * @param AccountEnrichedDto $accountEnrichedDto
     *
     * @return AccountEnrichedDto
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function withUsers(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto
    {
        return $accountEnrichedDto->withUsers(
            $this->accountToUserRepository->getUsersByAccountId($accountEnrichedDto->getId())->getDataAsArray()
        );
    }

    /**
     * @param AccountEnrichedDto $accountEnrichedDto
     *
     * @return AccountEnrichedDto
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function withUserGroups(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto
    {
        return $accountEnrichedDto->withUserGroups(
            $this->accountToUserGroupRepository
                ->getUserGroupsByAccountId($accountEnrichedDto->getId())
                ->getDataAsArray()
        );
    }

    /**
     * @param AccountEnrichedDto $accountEnrichedDto
     *
     * @return AccountEnrichedDto
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function withTags(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto
    {
        return $accountEnrichedDto->withTags(
            $this->accountToTagRepository->getTagsByAccountId($accountEnrichedDto->getId())->getDataAsArray()
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementViewCounter(int $id): bool
    {
        return $this->accountRepository->incrementViewCounter($id)->getAffectedNumRows() === 1;
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function incrementDecryptCounter(int $id): bool
    {
        return $this->accountRepository->incrementDecryptCounter($id)->getAffectedNumRows() === 1;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function getPasswordForId(int $id): Account
    {
        $result = $this->accountRepository->getPasswordForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Account not found'));
        }

        return $result->getData(Account::class);
    }

    /**
     * @param  int  $id
     *
     * @return AccountDataView
     * @throws SPException
     * @throws NoSuchItemException
     */
    public function getByIdEnriched(int $id): AccountDataView
    {
        $result = $this->accountRepository->getByIdEnriched($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return $result->getData(AccountDataView::class);
    }

    /**
     * Update accounts in bulk mode
     *
     * @param AccountUpdateBulkDto $accountUpdateBulkDto
     *
     * @throws ServiceException
     */
    public function updateBulk(AccountUpdateBulkDto $accountUpdateBulkDto): void
    {
        $this->accountRepository->transactionAware(
            function () use ($accountUpdateBulkDto) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();

                $userCanChangePermissions = AccountAclService::getShowPermission($userData, $userProfile);

                foreach ($accountUpdateBulkDto->getAccountUpdateDto() as $accountId => $accountUpdateDto) {
                    if ($userCanChangePermissions) {
                        $account = $this->getById($accountId);

                        $changeOwner = $this->userCanChangeOwner($userData, $userProfile, $account);
                        $changeUserGroup = $this->userCanChangeGroup($userData, $userProfile, $account);
                    } else {
                        $changeOwner = false;
                        $changeUserGroup = false;
                    }

                    $this->addHistory($accountId);

                    $this->accountRepository->updateBulk(
                        $accountId,
                        Account::update($accountUpdateDto),
                        $changeOwner,
                        $changeUserGroup
                    );

                    $this->accountItemsService->updateItems($accountId, $userCanChangePermissions, $accountUpdateDto);
                }
            },
            $this
        );
    }

    /**
     * @param  int  $id
     *
     * @return Account
     * @throws SPException
     * @throws NoSuchItemException
     */
    public function getById(int $id): Account
    {
        $result = $this->accountRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return $result->getData(Account::class);
    }

    /**
     * @param UserLoginResponse $userData
     * @param ProfileData $userProfile
     * @param Account $account
     *
     * @return bool
     */
    protected function userCanChangeOwner(
        UserLoginResponse $userData,
        ProfileData $userProfile,
        Account $account
    ): bool {
        return $userData->getIsAdminApp() || $userData->getIsAdminAcc()
               || ($userProfile->isAccPermission() && $userData->getId() === $account->getUserId());
    }

    /**
     * @param UserLoginResponse $userData
     * @param ProfileData $userProfile
     * @param Account $account
     *
     * @return bool
     */
    protected function userCanChangeGroup(
        UserLoginResponse $userData,
        ProfileData $userProfile,
        Account $account
    ): bool {
        return $this->userCanChangeOwner($userData, $userProfile, $account)
               || ($userProfile->isAccPermission() && $userData->getUserGroupId() === $account->getUserGroupId());
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
                $this->getById($accountId),
                !$isDelete,
                $isDelete,
                $this->configService->getByParam('masterPwd')
            )
        );
    }

    /**
     * @param AccountCreateDto $accountCreateDto
     *
     * @return int
     * @throws ServiceException
     */
    public function create(AccountCreateDto $accountCreateDto): int
    {
        return $this->accountRepository->transactionAware(
            function () use ($accountCreateDto) {
                $userData = $this->context->getUserData();

                $userCanChangePermissions =
                    AccountAclService::getShowPermission($userData, $this->context->getUserProfile());

                if (!$userCanChangePermissions) {
                    $accountCreateDto = AccountService::buildWithUserData($userData, $accountCreateDto);
                }

                $accountCreateDto = $accountCreateDto->withEncryptedPassword(
                    $this->accountCryptService->getPasswordEncrypted($accountCreateDto->getPass())
                );

                $accountCreateDto = $this->setPresetPrivate($accountCreateDto);

                $accountId = $this->accountRepository->create(Account::create($accountCreateDto))->getLastId();

                $this->accountItemsService->addItems($userCanChangePermissions, $accountId, $accountCreateDto);

                $this->accountPresetService->addPresetPermissions($accountId);

                return $accountId;
            },
            $this
        );
    }

    /**
     * @param UserLoginResponse $userData
     * @param AccountCreateDto $accountCreateDto
     *
     * @return AccountCreateDto
     */
    private static function buildWithUserData(
        UserLoginResponse $userData,
        AccountCreateDto $accountCreateDto
    ): AccountCreateDto {
        return $accountCreateDto->withUserGroupId($userData->getUserGroupId())->withUserId($userData->getId());
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
        $userData = $this->context->getUserData();
        $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE);

        if ($itemPreset !== null && $itemPreset->getFixed()) {
            $accountPrivate = $itemPreset->hydrate(AccountPrivate::class);

            if ($accountDto instanceof AccountUpdateDto && null !== $accountId) {
                $account = $this->getById($accountId);
                $accountDto =
                    $accountDto->withUserId($account->getUserId())->withUserGroupId($account->getUserGroupId());
            }

            $privateUser = $userData->getId() === $accountDto->getUserId()
                           && $accountPrivate->isPrivateUser();
            $privateGroup = $userData->getUserGroupId() === $accountDto->getUserGroupId()
                            && $accountPrivate->isPrivateGroup();

            return $accountDto->withPrivate($privateUser)->withPrivateGroup($privateGroup);
        }

        return $accountDto;
    }

    /**
     * Updates external items for the account
     *
     * @param  int  $id
     * @param AccountUpdateDto $accountUpdateDto
     *
     * @throws ServiceException
     */
    public function update(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->accountRepository->transactionAware(
            function () use ($id, $accountUpdateDto) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();

                $userCanChangePermissions = AccountAclService::getShowPermission($userData, $userProfile);

                if ($userCanChangePermissions) {
                    $account = $this->getById($id);

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
                    Account::update($accountUpdateDto),
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
     * @param  int  $id
     * @param AccountUpdateDto $accountUpdateDto
     *
     * @throws ServiceException
     */
    public function editPassword(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->accountRepository->transactionAware(
            function () use ($id, $accountUpdateDto) {
                $this->addHistory($id);

                $encryptedPassword = $this->accountCryptService->getPasswordEncrypted($accountUpdateDto->getPass());

                $this->accountRepository->editPassword(
                    $id,
                    Account::updatePassword($accountUpdateDto->withEncryptedPassword($encryptedPassword))
                );
            },
            $this
        );
    }

    /**
     * Updates an already encrypted password data from a master password changing action
     *
     * @param  int  $id
     * @param EncryptedPassword $encryptedPassword
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function updatePasswordMasterPass(int $id, EncryptedPassword $encryptedPassword): void
    {
        $result = $this->accountRepository->updatePassword($id, $encryptedPassword);

        if ($result->getAffectedNumRows() === 0) {
            throw new ServiceException(__u('Error while updating the password'));
        }
    }

    /**
     * @param AccountHistoryDto $accountHistoryDto
     *
     * @throws ServiceException
     */
    public function restoreModified(AccountHistoryDto $accountHistoryDto): void
    {
        $this->accountRepository->transactionAware(
            function () use ($accountHistoryDto) {
                $this->addHistory($accountHistoryDto->getAccountId());

                $result = $this->accountRepository->restoreModified(
                    $accountHistoryDto->getAccountId(),
                    Account::restoreModified($accountHistoryDto, $this->context->getUserData()->getId())
                );

                if ($result->getAffectedNumRows() === 0) {
                    throw new ServiceException(__u('Error on restoring the account'));
                }
            },
            $this
        );
    }

    /**
     * @param AccountHistoryDto $accountHistoryDto
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function restoreRemoved(AccountHistoryDto $accountHistoryDto): void
    {
        $result = $this->accountRepository->createRemoved(
            Account::restoreRemoved($accountHistoryDto, $this->context->getUserData()->getId())
        );

        if ($result->getAffectedNumRows() === 0) {
            throw new ServiceException(__u('Error on restoring the account'));
        }
    }

    /**
     * @throws ServiceException
     */
    public function delete(int $id): AccountServiceInterface
    {
        $this->accountRepository->transactionAware(
            function () use ($id) {
                $this->addHistory($id, 1);

                if ($this->accountRepository->delete($id)->getAffectedNumRows() === 0) {
                    throw new NoSuchItemException(__u('Account not found'));
                }
            },
            $this
        );

        return $this;
    }

    /**
     * @param  int[]  $ids
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void
    {
        if ($this->accountRepository->deleteByIdBatch($ids)->getAffectedNumRows() === 0) {
            throw new ServiceException(__u('Error while deleting the accounts'));
        }
    }

    /**
     * @param  int|null  $id
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getForUser(?int $id = null): array
    {
        return $this->accountRepository->getForUser($id)->getDataAsArray();
    }

    /**
     * @param  int  $id
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getLinked(int $id): array
    {
        return $this->accountRepository->getLinked($id)->getDataAsArray();
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function getPasswordHistoryForId(int $id): Simple
    {
        $result = $this->accountRepository->getPasswordHistoryForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return $result->getData(Simple::class);
    }

    /**
     * @return AccountData[]
     * @throws SPException
     */
    public function getAllBasic(): array
    {
        return $this->accountRepository->getAll()->getDataAsArray();
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->accountRepository->search($itemSearchData);
    }

    /**
     * Devolver el número total de cuentas
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getTotalNumAccounts(): int
    {
        $data = $this->accountRepository->getTotalNumAccounts()->getData(Simple::class);

        return (int)$data['num'];
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function getDataForLink(int $id): Simple
    {
        $result = $this->accountRepository->getDataForLink($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return $result->getData(Simple::class);
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return Simple[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getAccountsPassData(): array
    {
        return $this->accountRepository->getAccountsPassData()->getDataAsArray(Simple::class);
    }
}
