<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Account;

use Defuse\Crypto\Exception\CryptoException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\AccountPassData;
use SP\DataModel\Dto\AccountDetailsResponse;
use SP\DataModel\Dto\AccountHistoryCreateDto;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\Repositories\Account\AccountRepository;
use SP\Repositories\Account\AccountToTagRepository;
use SP\Repositories\Account\AccountToUserGroupRepository;
use SP\Repositories\Account\AccountToUserRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Config\ConfigService;
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Services\ItemPreset\ItemPresetService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountService
 *
 * @package SP\Services\Account
 */
final class AccountService extends Service implements AccountServiceInterface
{
    use ServiceItemTrait;

    protected ?AccountRepository $accountRepository = null;
    protected ?AccountToUserGroupRepository $accountToUserGroupRepository = null;
    protected ?AccountToUserRepository $accountToUserRepository = null;
    protected ?AccountToTagRepository $accountToTagRepository = null;
    protected ?ItemPresetService $itemPresetService = null;

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
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getPasswordForId(int $id): AccountPassData
    {
        $queryFilter = $this->dic->get(AccountFilterUser::class)->getFilter();

        $result = $this->accountRepository->getPasswordForId($id, $queryFilter);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Account not found'));
        }

        return $result->getData();
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
            $this->context->getUserProfile());

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
     * @throws \SP\Services\ServiceException
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
        $itemPresetData = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION);

        if ($itemPresetData !== null
            && $itemPresetData->getFixed()
        ) {
            $userData = $this->context->getUserData();
            $accountPermission = $itemPresetData->hydrate(AccountPermission::class);

            $accountRequest = new AccountRequest();
            $accountRequest->id = $accountId;
            $accountRequest->usersView = array_diff($accountPermission->getUsersView(), [$userData->getId()]);
            $accountRequest->usersEdit = array_diff($accountPermission->getUsersEdit(), [$userData->getId()]);
            $accountRequest->userGroupsView = array_diff($accountPermission->getUserGroupsView(), [$userData->getUserGroupId()]);
            $accountRequest->userGroupsEdit = array_diff($accountPermission->getUserGroupsEdit(), [$userData->getUserGroupId()]);

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
     * @throws QueryException
     * @throws ConstraintException
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
     * Updates external items for the account
     *
     * @throws Exception
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
                                    && ($userData->getUserGroupId() === $account->getUserGroupId()))
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
        $accountHistoryRepository = $this->dic->get(AccountHistoryService::class);
        $configService = $this->dic->get(ConfigService::class);

        return $accountHistoryRepository->create(
            new AccountHistoryCreateDto(
                $accountId,
                !$isDelete,
                $isDelete,
                $configService->getByParam('masterPwd'))
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
     * @throws ServiceException
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
     * @throws Exception
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
     * @param int $historyId
     * @param int $accountId
     *
     * @throws \SP\Services\ServiceException
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
     * @throws \SP\Services\ServiceException
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
     * @param int[] $ids
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
        $queryFilter = $this->dic->get(AccountFilterUser::class)->getFilter();

        if (null !== $accountId) {
            $queryFilter->addFilter('Account.id <> ? AND (Account.parentId = 0 OR Account.parentId IS NULL)', [$accountId]);
        }

        return $this->accountRepository->getForUser($queryFilter)->getDataAsArray();
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getLinked(int $accountId): array
    {
        $queryFilter = $this->dic->get(AccountFilterUser::class)->getFilter();

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
        $queryFilter = $this->dic->get(AccountFilterUser::class)->getFilterHistory();
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
     * @throws QueryException
     * @throws ConstraintException
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
     * @throws \SP\Repositories\NoSuchItemException
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
     * @throws QueryException
     * @throws SPException
     * @throws ConstraintException
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter): QueryResult
    {
        $accountFilterUser = $this->dic->get(AccountFilterUser::class);

        return $this->accountRepository->getByFilter(
            $accountSearchFilter,
            $accountFilterUser->getFilter($accountSearchFilter->getGlobalSearch())
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->accountRepository = $this->dic->get(AccountRepository::class);
        $this->accountToUserRepository = $this->dic->get(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->dic->get(AccountToUserGroupRepository::class);
        $this->accountToTagRepository = $this->dic->get(AccountToTagRepository::class);
        $this->itemPresetService = $this->dic->get(ItemPresetService::class);
    }
}