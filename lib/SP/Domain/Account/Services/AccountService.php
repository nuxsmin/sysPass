<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\Domain\Account\Adapters\AccountData;
use SP\Domain\Account\Adapters\AccountPassData;
use SP\Domain\Account\Dtos\AccountBulkRequest;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\AccountPasswordRequest;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Account\Ports\AccountCryptServiceInterface;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Account\Ports\AccountRepositoryInterface;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Domain\Account\Ports\AccountToTagRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Config\Ports\ConfigServiceInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use function SP\__u;
use function SP\logger;

/**
 * Class AccountService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountService extends Service implements AccountServiceInterface
{
    use ServiceItemTrait;

    public function __construct(
        Application $application,
        private AccountRepositoryInterface $accountRepository,
        private AccountToUserGroupRepositoryInterface $accountToUserGroupRepository,
        private AccountToUserRepositoryInterface $accountToUserRepository,
        private AccountToTagRepositoryInterface $accountToTagRepository,
        private ItemPresetServiceInterface $itemPresetService,
        private AccountHistoryServiceInterface $accountHistoryService,
        private ConfigServiceInterface $configService,
        private AccountCryptServiceInterface $accountCryptService
    ) {
        parent::__construct($application);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withUsersById(AccountEnrichedDto $accountDetailsResponse): AccountServiceInterface
    {
        $accountDetailsResponse->setUsers(
            $this->accountToUserRepository->getUsersByAccountId($accountDetailsResponse->getId())
                ->getDataAsArray()
        );

        return $this;
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withUserGroupsById(AccountEnrichedDto $accountDetailsResponse): AccountServiceInterface
    {
        $accountDetailsResponse->setUserGroups(
            $this->accountToUserGroupRepository->getUserGroupsByAccountId($accountDetailsResponse->getId())
                ->getDataAsArray()
        );

        return $this;
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withTagsById(AccountEnrichedDto $accountDetailsResponse): AccountServiceInterface
    {
        $accountDetailsResponse->setTags(
            $this->accountToTagRepository->getTagsByAccountId($accountDetailsResponse->getId())
                ->getDataAsArray()
        );

        return $this;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function incrementViewCounter(int $id): bool
    {
        return $this->accountRepository->incrementViewCounter($id);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function incrementDecryptCounter(int $id): bool
    {
        return $this->accountRepository->incrementDecryptCounter($id);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getPasswordForId(int $id): AccountPassData
    {
        $result = $this->accountRepository->getPasswordForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Account not found'));
        }

        return $result->getData();
    }

    /**
     * @param  \SP\DataModel\AccountHistoryData  $data
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function createFromHistory(AccountHistoryData $data): int
    {
        $accountRequest = new AccountRequest();
        $accountRequest->name = $data->getName();
        $accountRequest->categoryId = $data->getCategoryId();
        $accountRequest->clientId = $data->getClientId();
        $accountRequest->url = $data->getUrl();
        $accountRequest->login = $data->getLogin();
        $accountRequest->pass = $data->getPass();
        $accountRequest->key = $data->getKey();
        $accountRequest->notes = $data->getNotes();
        $accountRequest->userId = $data->getUserId();
        $accountRequest->userGroupId = $data->getUserGroupId();
        $accountRequest->passDateChange = $data->getPassDateChange();
        $accountRequest->parentId = $data->getParentId();
        $accountRequest->isPrivate = $data->getIsPrivate();
        $accountRequest->isPrivateGroup = $data->getIsPrivateGroup();

        return $this->accountRepository->create($accountRequest);
    }

    /**
     * @throws QueryException
     * @throws SPException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     */
    public function create(AccountRequest $accountRequest): int
    {
        $userData = $this->context->getUserData();

        $accountRequest->changePermissions = AccountAclService::getShowPermission(
            $userData,
            $this->context->getUserProfile()
        );

        if (empty($accountRequest->userGroupId)
            || !$accountRequest->changePermissions
        ) {
            $accountRequest->userGroupId = $userData->getUserGroupId();
        }

        if (empty($accountRequest->userId)
            || !$accountRequest->changePermissions
        ) {
            $accountRequest->userId = $userData->getId();
        }

        if (empty($accountRequest->key)) {
            $encryptedPassword = $this->accountCryptService->getPasswordEncrypted($accountRequest->pass);

            $accountRequest->pass = $encryptedPassword->getPass();
            $accountRequest->key = $encryptedPassword->getKey();
        }

        $this->setPresetPrivate($accountRequest);

        $accountRequest->id = $this->accountRepository->create($accountRequest);

        $this->addItems($accountRequest);

        $this->addPresetPermissions($accountRequest->id);

        return $accountRequest->id;
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws NoSuchItemException
     */
    private function setPresetPrivate(AccountRequest $accountRequest): void
    {
        $userData = $this->context->getUserData();
        $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE);

        if ($itemPreset !== null
            && $itemPreset->getFixed()
        ) {
            $accountPrivate = $itemPreset->hydrate(AccountPrivate::class);

            $userId = $accountRequest->userId;

            if ($userId === null && $accountRequest->id > 0) {
                $userId = $this->getById($accountRequest->id)->getAccountVData()->getUserId();
            }

            if ($userData->getId() === $userId) {
                $accountRequest->isPrivate = (int)$accountPrivate->isPrivateUser();
            }

            if ($userData->getUserGroupId() === $accountRequest->userGroupId) {
                $accountRequest->isPrivateGroup = (int)$accountPrivate->isPrivateGroup();
            }
        }
    }

    /**
     * @param  int  $id
     *
     * @return \SP\Domain\Account\Dtos\AccountEnrichedDto
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getById(int $id): AccountEnrichedDto
    {
        $result = $this->accountRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return new AccountEnrichedDto($result->getData());
    }

    /**
     * Adds external items to the account
     */
    private function addItems(AccountRequest $accountRequest): void
    {
        try {
            if ($accountRequest->changePermissions) {
                if (is_array($accountRequest->userGroupsView)
                    && count($accountRequest->userGroupsView) !== 0
                ) {
                    $this->accountToUserGroupRepository->addByType($accountRequest, false);
                }

                if (is_array($accountRequest->userGroupsEdit)
                    && count($accountRequest->userGroupsEdit) !== 0
                ) {
                    $this->accountToUserGroupRepository->addByType($accountRequest, true);
                }

                if (is_array($accountRequest->usersView)
                    && count($accountRequest->usersView) !== 0
                ) {
                    $this->accountToUserRepository->addByType($accountRequest, false);
                }

                if (is_array($accountRequest->usersEdit)
                    && count($accountRequest->usersEdit) !== 0
                ) {
                    $this->accountToUserRepository->addByType($accountRequest, true);
                }
            }

            if (is_array($accountRequest->tags)
                && count($accountRequest->tags) !== 0
            ) {
                $this->accountToTagRepository->add($accountRequest);
            }
        } catch (SPException $e) {
            logger($e->getMessage());
        }
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     */
    private function addPresetPermissions(int $accountId): void
    {
        $itemPresetData =
            $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION);

        if ($itemPresetData !== null
            && $itemPresetData->getFixed()
        ) {
            $userData = $this->context->getUserData();
            $accountPermission = $itemPresetData->hydrate(AccountPermission::class);

            $usersView = array_diff($accountPermission->getUsersView(), [$userData->getId()]);
            $usersEdit = array_diff($accountPermission->getUsersEdit(), [$userData->getId()]);
            $userGroupsView = array_diff($accountPermission->getUserGroupsView(), [$userData->getUserGroupId()]);
            $userGroupsEdit = array_diff($accountPermission->getUserGroupsEdit(), [$userData->getUserGroupId()]);

            if (count($usersView) !== 0) {
                $this->accountToUserRepository->addByType($accountId, $usersView, false);
            }

            if (count($usersEdit) !== 0) {
                $this->accountToUserRepository->addByType($accountId, $usersEdit, true);
            }

            if (count($userGroupsView) !== 0) {
                $this->accountToUserGroupRepository->addByType($accountId, $userGroupsView, false);
            }

            if (count($userGroupsEdit) !== 0) {
                $this->accountToUserGroupRepository->addByType($accountId, $userGroupsEdit, true);
            }
        }
    }

    /**
     * Updates external items for the account
     *
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $accountRequest
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function update(AccountRequest $accountRequest): void
    {
        $this->accountRepository->transactionAware(
            function () use ($accountRequest) {
                $userData = $this->context->getUserData();
                $userProfile = $this->context->getUserProfile() ?? new ProfileData();

                $accountRequest->changePermissions =
                    AccountAclService::getShowPermission($userData, $userProfile);

                if ($accountRequest->changePermissions) {
                    $account = $this->getById($accountRequest->id)->getAccountVData();

                    $accountRequest->changeOwner = $accountRequest->userId > 0
                                                   && ($userData->getIsAdminApp()
                                                       || $userData->getIsAdminAcc()
                                                       || ($userProfile->isAccPermission()
                                                           && $userData->getId() === $account->getUserId()));

                    $accountRequest->changeUserGroup = $accountRequest->userGroupId > 0
                                                       && ($userData->getIsAdminApp()
                                                           || $userData->getIsAdminAcc()
                                                           || (($userProfile->isAccPermission()
                                                                && ($userData->getUserGroupId()
                                                                    === $account->getUserGroupId()))
                                                               || $userData->getId() === $account->getUserId()));
                }

                $this->addHistory($accountRequest->id);

                $this->setPresetPrivate($accountRequest);

                $this->accountRepository->update($accountRequest);

                $this->updateItems($accountRequest);

                $this->addPresetPermissions($accountRequest->id);
            }
        );
    }

    /**
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     */
    private function addHistory(int $accountId, bool $isDelete = false): int
    {
        return $this->accountHistoryService->create(
            new AccountHistoryCreateDto(
                $accountId,
                !$isDelete,
                $isDelete,
                $this->configService->getByParam('masterPwd')
            )
        );
    }

    /**
     * Updates external items for the account
     *
     * @throws QueryException
     * @throws ConstraintException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function updateItems(AccountRequest $accountRequest): void
    {
        if ($accountRequest->changePermissions) {
            if ($accountRequest->userGroupsView !== null) {
                if (count($accountRequest->userGroupsView) > 0) {
                    $this->accountToUserGroupRepository->transactionAware(
                        function () use ($accountRequest) {
                            $this->accountToUserGroupRepository
                                ->deleteTypeByAccountId($accountRequest->id, false);
                            $this->accountToUserGroupRepository
                                ->addByType($accountRequest->id, $accountRequest->userGroupsView, false);
                        }
                    );
                } else {
                    $this->accountToUserGroupRepository->deleteTypeByAccountId($accountRequest->id, false);
                }
            }

            if ($accountRequest->userGroupsEdit !== null) {
                if (count($accountRequest->userGroupsEdit) > 0) {
                    $this->accountToUserGroupRepository->transactionAware(
                        function () use ($accountRequest) {
                            $this->accountToUserGroupRepository
                                ->deleteTypeByAccountId($accountRequest->id, true);
                            $this->accountToUserGroupRepository
                                ->addByType($accountRequest->id, $accountRequest->userGroupsEdit, true);
                        }
                    );
                } else {
                    $this->accountToUserGroupRepository->deleteTypeByAccountId($accountRequest->id, true);
                }
            }

            if ($accountRequest->usersView !== null) {
                if (count($accountRequest->usersView) > 0) {
                    $this->accountToUserRepository->transactionAware(
                        function () use ($accountRequest) {
                            $this->accountToUserRepository
                                ->deleteTypeByAccountId($accountRequest->id, false);
                            $this->accountToUserRepository
                                ->addByType($accountRequest->id, $accountRequest->usersView, false);
                        }
                    );
                } else {
                    $this->accountToUserRepository->deleteTypeByAccountId($accountRequest->id, false);
                }
            }

            if ($accountRequest->usersEdit !== null) {
                if (count($accountRequest->usersEdit) > 0) {
                    $this->accountToUserRepository->transactionAware(
                        function () use ($accountRequest) {
                            $this->accountToUserRepository
                                ->deleteTypeByAccountId($accountRequest->id, true);
                            $this->accountToUserRepository
                                ->addByType($accountRequest->id, $accountRequest->usersEdit, true);
                        }
                    );
                } else {
                    $this->accountToUserRepository->deleteTypeByAccountId($accountRequest->id, true);
                }
            }
        }

        if ($accountRequest->tags !== null) {
            if (count($accountRequest->tags) > 0) {
                $this->accountToTagRepository->transactionAware(
                    function () use ($accountRequest) {
                        $this->accountToTagRepository->deleteByAccountId($accountRequest->id);
                        $this->accountToTagRepository->add($accountRequest);
                    }
                );
            } else {
                $this->accountToTagRepository->deleteByAccountId($accountRequest->id);
            }
        }
    }

    /**
     * Update accounts in bulk mode
     *
     * @param  \SP\Domain\Account\Dtos\AccountBulkRequest  $request
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function updateBulk(AccountBulkRequest $request): void
    {
        $this->accountRepository->transactionAware(
            function () use ($request) {
                foreach ($request->getItemsId() as $itemId) {
                    $accountRequest = $request->getAccountRequestForId($itemId);

                    $this->addHistory($accountRequest->id);

                    $this->accountRepository->updateBulk($accountRequest);

                    $this->updateItems($accountRequest);
                }
            }
        );
    }

    /**
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $accountRequest
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function editPassword(AccountRequest $accountRequest): void
    {
        $this->accountRepository->transactionAware(
            function () use ($accountRequest) {
                $this->addHistory($accountRequest->id);

                $pass = $this->getPasswordEncrypted($accountRequest->pass);

                $accountRequest->pass = $pass['pass'];
                $accountRequest->key = $pass['key'];

                $this->accountRepository->editPassword($accountRequest);
            }
        );
    }

    /**
     * Updates an already encrypted password data from a master password changing action
     *
     * @param  \SP\Domain\Account\Dtos\AccountPasswordRequest  $accountRequest
     *
     * @return void
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function updatePasswordMasterPass(AccountPasswordRequest $accountRequest): void
    {
        if (!$this->accountRepository->updatePassword($accountRequest)) {
            throw new ServiceException(__u('Error while updating the password'));
        }
    }

    /**
     * @param  int  $historyId
     * @param  int  $accountId
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function editRestore(int $historyId, int $accountId): void
    {
        $accountHistoryData = $this->accountHistoryService->getById($historyId);

        $this->accountRepository->transactionAware(
            function () use ($historyId, $accountId, $accountHistoryData) {
                $this->addHistory($accountId);

                if (!$this->accountRepository->editRestore(
                    $accountHistoryData,
                    $this->context->getUserData()->getId()
                )) {
                    throw new ServiceException(__u('Error on restoring the account'));
                }
            }
        );
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function delete(int $id): AccountServiceInterface
    {
        $this->accountRepository->transactionAware(
            function () use ($id) {
                $this->addHistory($id, 1);

                if ($this->accountRepository->delete($id)) {
                    throw new NoSuchItemException(__u('Account not found'));
                }
            }
        );

        return $this;
    }

    /**
     * @param  int[]  $ids
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): AccountServiceInterface
    {
        if ($this->accountRepository->deleteByIdBatch($ids) === 0) {
            throw new ServiceException(__u('Error while deleting the accounts'));
        }

        return $this;
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getForUser(?int $accountId = null): array
    {
        return $this->accountRepository->getForUser($accountId)->getDataAsArray();
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getLinked(int $accountId): array
    {
        return $this->accountRepository->getLinked($accountId)->getDataAsArray();
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function getPasswordHistoryForId(int $id): AccountPassData
    {
        $result = $this->accountRepository->getPasswordHistoryForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return $result->getData();
    }

    /**
     * @return AccountData[]
     */
    public function getAllBasic(): array
    {
        return $this->accountRepository->getAll()->getDataAsArray();
    }

    /**
     * @param  \SP\DataModel\ItemSearchData  $itemSearchData
     *
     * @return \SP\Infrastructure\Database\QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->accountRepository->search($itemSearchData);
    }

    /**
     * Devolver el número total de cuentas
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getTotalNumAccounts(): int
    {
        return (int)$this->accountRepository->getTotalNumAccounts()->num;
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getDataForLink(int $id): AccountExtData
    {
        $result = $this->accountRepository->getDataForLink($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return $result->getData();
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAccountsPassData(): array
    {
        return $this->accountRepository->getAccountsPassData()->getDataAsArray();
    }
}
