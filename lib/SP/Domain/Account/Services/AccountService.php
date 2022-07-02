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

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Application;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountDetailsResponse;
use SP\DataModel\Dto\AccountHistoryCreateDto;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\Domain\Account\AccountHistoryServiceInterface;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\Account\In\AccountRepositoryInterface;
use SP\Domain\Account\In\AccountToTagRepositoryInterface;
use SP\Domain\Account\In\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\In\AccountToUserRepositoryInterface;
use SP\Domain\Account\Out\AccountData;
use SP\Domain\Account\Out\AccountPassData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Config\ConfigServiceInterface;
use SP\Domain\ItemPreset\ItemPresetInterface;
use SP\Domain\ItemPreset\ItemPresetServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountService extends Service implements AccountServiceInterface
{
    use ServiceItemTrait;

    private AccountRepositoryInterface $accountRepository;
    private AccountToUserGroupRepositoryInterface $accountToUserGroupRepository;
    private AccountToUserRepositoryInterface $accountToUserRepository;
    private AccountToTagRepositoryInterface $accountToTagRepository;
    private ItemPresetServiceInterface $itemPresetService;
    private AccountHistoryServiceInterface $accountHistoryService;
    private ConfigServiceInterface $configService;
    private AccountFilterUser $accountFilterUser;

    public function __construct(
        Application $application,
        AccountRepositoryInterface $accountRepository,
        AccountToUserGroupRepositoryInterface $accountToUserGroupRepository,
        AccountToUserRepositoryInterface $accountToUserRepository,
        AccountToTagRepositoryInterface $accountToTagRepository,
        ItemPresetServiceInterface $itemPresetService,
        AccountHistoryServiceInterface $accountHistoryService,
        ConfigServiceInterface $configService,
        AccountFilterUser $accountFilterUser
    ) {
        $this->accountRepository = $accountRepository;
        $this->accountToUserGroupRepository = $accountToUserGroupRepository;
        $this->accountToUserRepository = $accountToUserRepository;
        $this->accountToTagRepository = $accountToTagRepository;
        $this->itemPresetService = $itemPresetService;
        $this->accountHistoryService = $accountHistoryService;
        $this->configService = $configService;
        $this->accountFilterUser = $accountFilterUser;

        parent::__construct($application);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withUsersById(AccountDetailsResponse $accountDetailsResponse): AccountService
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
    public function withUserGroupsById(AccountDetailsResponse $accountDetailsResponse): AccountService
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
    public function withTagsById(AccountDetailsResponse $accountDetailsResponse): AccountService
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
        $queryFilter = $this->accountFilterUser->getFilter();

        $result = $this->accountRepository->getPasswordForId($id, $queryFilter);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Account not found'));
        }

        return $result->getData();
    }

    /**
     * @param  \SP\DataModel\AccountHistoryData  $data
     *
     * @return int
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
            $pass = $this->getPasswordEncrypted($accountRequest->pass);

            $accountRequest->pass = $pass['pass'];
            $accountRequest->key = $pass['key'];
        }

        $this->setPresetPrivate($accountRequest);

        $accountRequest->id = $this->accountRepository->create($accountRequest);

        $this->addItems($accountRequest);

        $this->addPresetPermissions($accountRequest->id);

        return $accountRequest->id;
    }

    /**
     * Devolver los datos de la clave encriptados
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getPasswordEncrypted(string $pass, ?string $masterPass = null): array
    {
        try {
            if ($masterPass === null) {
                $masterPass = $this->getMasterKeyFromContext();
            }

            if (empty($masterPass)) {
                throw new ServiceException(__u('Master password not set'));
            }

            $out['key'] = Crypt::makeSecuredKey($masterPass);
            $out['pass'] = Crypt::encrypt($pass, $out['key'], $masterPass);

            if (strlen($out['pass']) > 1000 || strlen($out['key']) > 1000) {
                throw new ServiceException(__u('Internal error'));
            }

            return $out;
        } catch (CryptoException $e) {
            throw new ServiceException(__u('Internal error'));
        }
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
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ConstraintException
     */
    public function getById(int $id): AccountDetailsResponse
    {
        $result = $this->accountRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return new AccountDetailsResponse($id, $result->getData());
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

            $accountRequest = new AccountRequest();
            $accountRequest->id = $accountId;
            $accountRequest->usersView = array_diff($accountPermission->getUsersView(), [$userData->getId()]);
            $accountRequest->usersEdit = array_diff($accountPermission->getUsersEdit(), [$userData->getId()]);
            $accountRequest->userGroupsView =
                array_diff($accountPermission->getUserGroupsView(), [$userData->getUserGroupId()]);
            $accountRequest->userGroupsEdit =
                array_diff($accountPermission->getUserGroupsEdit(), [$userData->getUserGroupId()]);

            if (!empty($accountRequest->usersView)) {
                $this->accountToUserRepository->addByType($accountRequest, false);
            }

            if (!empty($accountRequest->usersEdit)) {
                $this->accountToUserRepository->addByType($accountRequest, true);
            }

            if (!empty($accountRequest->userGroupsView)) {
                $this->accountToUserGroupRepository->addByType($accountRequest, false);
            }

            if (!empty($accountRequest->userGroupsEdit)) {
                $this->accountToUserGroupRepository->addByType($accountRequest, true);
            }
        }
    }

    /**
     * Updates external items for the account
     *
     * @param  \SP\Domain\Account\Services\AccountRequest  $accountRequest
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function update(AccountRequest $accountRequest): void
    {
        $this->transactionAware(
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
     */
    private function updateItems(AccountRequest $accountRequest): void
    {
        if ($accountRequest->changePermissions) {
            if ($accountRequest->userGroupsView !== null) {
                if (count($accountRequest->userGroupsView) > 0) {
                    $this->accountToUserGroupRepository->updateByType($accountRequest, false);
                } else {
                    $this->accountToUserGroupRepository->deleteTypeByAccountId($accountRequest->id, false);
                }
            }

            if ($accountRequest->userGroupsEdit !== null) {
                if (count($accountRequest->userGroupsEdit) > 0) {
                    $this->accountToUserGroupRepository->updateByType($accountRequest, true);
                } else {
                    $this->accountToUserGroupRepository->deleteTypeByAccountId($accountRequest->id, true);
                }
            }

            if ($accountRequest->usersView !== null) {
                if (count($accountRequest->usersView) > 0) {
                    $this->accountToUserRepository->updateByType($accountRequest, false);
                } else {
                    $this->accountToUserRepository->deleteTypeByAccountId($accountRequest->id, false);
                }
            }

            if ($accountRequest->usersEdit !== null) {
                if (count($accountRequest->usersEdit) > 0) {
                    $this->accountToUserRepository->updateByType($accountRequest, true);
                } else {
                    $this->accountToUserRepository->deleteTypeByAccountId($accountRequest->id, true);
                }
            }
        }

        if ($accountRequest->tags !== null) {
            if (count($accountRequest->tags) > 0) {
                $this->accountToTagRepository->update($accountRequest);
            } else {
                $this->accountToTagRepository->deleteByAccountId($accountRequest->id);
            }
        }
    }

    /**
     * Update accounts in bulk mode
     *
     * @param  \SP\Domain\Account\Services\AccountBulkRequest  $request
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function updateBulk(AccountBulkRequest $request): void
    {
        $this->transactionAware(
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
     * @param  \SP\Domain\Account\Services\AccountRequest  $accountRequest
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function editPassword(AccountRequest $accountRequest): void
    {
        $this->transactionAware(function () use ($accountRequest) {
            $this->addHistory($accountRequest->id);

            $pass = $this->getPasswordEncrypted($accountRequest->pass);

            $accountRequest->pass = $pass['pass'];
            $accountRequest->key = $pass['key'];

            $this->accountRepository->editPassword($accountRequest);
        });
    }

    /**
     * Updates an already encrypted password data from a master password changing action
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePasswordMasterPass(AccountPasswordRequest $accountRequest): bool
    {
        return $this->accountRepository->updatePassword($accountRequest);
    }

    /**
     * @param  int  $historyId
     * @param  int  $accountId
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function editRestore(int $historyId, int $accountId): void
    {
        $this->transactionAware(
            function () use ($historyId, $accountId) {
                $this->addHistory($accountId);

                if (!$this->accountRepository->editRestore($historyId, $this->context->getUserData()->getId())) {
                    throw new ServiceException(__u('Error on restoring the account'));
                }
            }
        );
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function delete(int $id): AccountService
    {
        $this->transactionAware(function () use ($id) {
            $this->addHistory($id, 1);

            if ($this->accountRepository->delete($id) === 0) {
                throw new NoSuchItemException(__u('Account not found'));
            }
        });

        return $this;
    }

    /**
     * @param  int[]  $ids
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): AccountService
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
    public function getForUser(int $accountId = null): array
    {
        $queryFilter = $this->accountFilterUser->getFilter();

        if (null !== $accountId) {
            $queryFilter->addFilter(
                'Account.id <> ? AND (Account.parentId = 0 OR Account.parentId IS NULL)',
                [$accountId]
            );
        }

        return $this->accountRepository->getForUser($queryFilter)->getDataAsArray();
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getLinked(int $accountId): array
    {
        $queryFilter = $this->accountFilterUser->getFilter();

        $queryFilter->addFilter('Account.parentId = ?', [$accountId]);

        return $this->accountRepository->getLinked($queryFilter)->getDataAsArray();
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function getPasswordHistoryForId(int $id): AccountPassData
    {
        $queryFilter = $this->accountFilterUser->getFilterHistory();
        $queryFilter->addFilter('AccountHistory.id = ?', [$id]);

        $result = $this->accountRepository->getPasswordHistoryForId($queryFilter);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('The account doesn\'t exist'));
        }

        return $result->getData();
    }

    /**
     * @return AccountData[]
     * @throws QueryException
     * @throws ConstraintException
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

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param  \SP\Domain\Account\Services\AccountSearchFilter  $accountSearchFilter
     *
     * @return \SP\Infrastructure\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter): QueryResult
    {
        return $this->accountRepository->getByFilter(
            $accountSearchFilter,
            $this->accountFilterUser->getFilter($accountSearchFilter->getGlobalSearch())
        );
    }
}