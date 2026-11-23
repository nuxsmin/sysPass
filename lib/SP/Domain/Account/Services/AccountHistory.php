<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Domain\Account\Services;

use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Models\AccountItem;
use SP\Domain\Account\Ports\AccountHistoryRepository;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Domain\User\Ports\UserToUserGroupRepository;

use function SP\__u;

/**
 * Class AccountHistory
 */
final class AccountHistory extends Service implements AccountHistoryService
{
    /**
     * @param CustomFieldDataService<CustomFieldDataModel>|null $customFieldDataService
     * @param UserToUserGroupRepository<UserToUserGroupModel>|null $userToUserGroupRepository
     */
    public function __construct(
        ConfigFileService                                 $config,
        EventDispatcherInterface                          $eventDispatcher,
        Context                                           $context,
        private readonly AccountHistoryRepository         $accountHistoryRepository,
        private readonly ?AccountRepository               $accountRepository = null,
        private readonly ?AccountToUserRepository         $accountToUserRepository = null,
        private readonly ?AccountToUserGroupRepository    $accountToUserGroupRepository = null,
        private readonly ?CustomFieldDataService          $customFieldDataService = null,
        private readonly ?UserToUserGroupRepository       $userToUserGroupRepository = null,
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return AccountHistoryDto
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function getById(int $id): AccountHistoryDto
    {
        $results = $this->accountHistoryRepository->getById($id);

        if ($results->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Error while retrieving account\'s data'));
        }

        return AccountHistoryDto::fromResult($results, AccountHistoryModel::class);
    }

    /**
     * @return AccountHistoryModel[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByAccountId(int $accountId): array
    {
        return $this->accountHistoryRepository->getByAccountId($accountId)->getDataAsArray(AccountHistoryModel::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function reassignAccountId(int $oldAccountId, int $newAccountId, string $dateAdd): int
    {
        return $this->accountHistoryRepository->reassignAccountId($oldAccountId, $newAccountId, $dateAdd);
    }

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param int $id
     *
     * @return array Con los registros con id como clave y fecha - usuario como valor
     */
    public function getHistoryForAccount(int $id): array
    {
        return $this->accountHistoryRepository->getHistoryForAccount($id)->getDataAsArray();
    }

    /**
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->accountHistoryRepository->search($itemSearchData);
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountHistoryCreateDto $dto
     *
     * @return int
     */
    public function create(AccountHistoryCreateDto $dto): int
    {
        return $this->accountHistoryRepository->create($dto);
    }

    /**
     * @return array
     * @throws SPException
     */
    public function getAccountsPassData(): array
    {
        return $this->accountHistoryRepository->getAccountsPassData()->getDataAsArray();
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $id
     *
     * @throws ServiceException
     */
    public function delete(int $id): void
    {
        if ($this->accountRepository === null) {
            if (!$this->accountHistoryRepository->delete($id)) {
                throw new ServiceException(__u('Error while deleting the account'));
            }

            return;
        }

        if ($this->deleteByIdBatch([$id]) !== 1) {
            throw new ServiceException(__u('Error while deleting the account'));
        }
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @return int
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if ($this->accountRepository === null) {
            return $this->accountHistoryRepository->transactionAware(function () use ($ids) {
                return $this->accountHistoryRepository->deleteByIdBatch($ids);
            }, $this);
        }

        $uniqueIds = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if ($uniqueIds === []) {
            throw new ServiceException(__u('Error while deleting the accounts'));
        }

        return $this->accountHistoryRepository->transactionAware(function () use ($uniqueIds) {
            /** @var AccountHistoryModel[] $selectedRows */
            $selectedRows = $this->accountHistoryRepository->getByIds($uniqueIds)->getDataAsArray(AccountHistoryModel::class);

            if (count($selectedRows) !== count($uniqueIds)) {
                throw new NoSuchItemException(__u('Error while retrieving account\'s data'));
            }

            /** @var array<int, AccountHistoryModel[]> $byAccount */
            $byAccount = [];
            foreach ($selectedRows as $row) {
                $accId = (int)$row->getAccountId();
                $byAccount[$accId][] = $row;
            }

            foreach ($byAccount as $accountId => $accountSelectedRows) {
                $this->authorizeHistoryDeletion($accountId, $accountSelectedRows, $uniqueIds);
            }

            $this->customFieldDataService?->delete($uniqueIds, AclActionsInterface::ACCOUNT_HISTORY_VIEW);

            $deleted = $this->accountHistoryRepository->deleteByIdBatch($uniqueIds);

            if ($deleted !== count($uniqueIds)) {
                throw new ServiceException(__u('Error while deleting the accounts'));
            }

            return $deleted;
        }, $this);
    }

    /**
     * Deletes all the items for given accounts id
     *
     * @param int[] $ids
     *
     * @return int
     */
    public function deleteByAccountIdBatch(array $ids): int
    {
        return $this->accountHistoryRepository->deleteByAccountIdBatch($ids);
    }

    /**
     * @param int $accountId
     * @param EncryptedPassword $encryptedPassword
     *
     * @throws ServiceException
     */
    public function updatePasswordMasterPass(int $accountId, EncryptedPassword $encryptedPassword): void
    {
        if (!$this->accountHistoryRepository->updatePassword($accountId, $encryptedPassword)) {
            throw new ServiceException(__u('Error while updating the password'));
        }
    }

    /**
     * @param AccountHistoryModel[] $selectedRows
     * @param int[]                 $selectedIds
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    private function authorizeHistoryDeletion(int $accountId, array $selectedRows, array $selectedIds): void
    {
        $liveResult = $this->accountRepository->getById($accountId);

        if ($liveResult->getNumRows() > 0) {
            /** @var AccountModel $liveAccount */
            $liveAccount = $liveResult->getData(AccountModel::class);
            $this->assertCanEditLiveAccount($accountId, $liveAccount);

            return;
        }

        /** @var AccountHistoryModel[] $allRows */
        $allRows = $this->accountHistoryRepository->getByAccountId($accountId)->getDataAsArray(AccountHistoryModel::class);
        $deletionSnapshots = array_values(array_filter(
            $allRows,
            static fn(AccountHistoryModel $row): bool => (int)$row->getIsDeleted() === 1
        ));

        if (count($deletionSnapshots) !== 1) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $snap = $deletionSnapshots[0];
        $isValidSnap = (int)($snap->getParentId() ?? 0) > 0
            || ((string)($snap->getPass() ?? '') !== '' && (string)($snap->getKey() ?? '') !== '');

        if (!$isValidSnap) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $this->assertCanEditDeletedSnapshot($snap);

        foreach ($selectedRows as $row) {
            if ((int)$row->getIsDeleted() === 1) {
                $remainingOutsideBatch = array_filter(
                    $allRows,
                    static fn(AccountHistoryModel $r): bool => !in_array((int)$r->getId(), $selectedIds, true)
                );

                if ($remainingOutsideBatch !== []) {
                    throw new ServiceException(__u('Error while deleting the account'));
                }
            }
        }
    }

    /**
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function assertCanEditLiveAccount(int $accountId, AccountModel $liveAccount): void
    {
        $userData = $this->context->getUserData();

        if ((bool)$liveAccount->getIsPrivate() && (int)$liveAccount->getUserId() !== $userData->id) {
            throw new AccountPermissionException(SPException::INFO);
        }

        if ((bool)$liveAccount->getIsPrivateGroup() && (int)$liveAccount->getUserGroupId() !== $userData->userGroupId) {
            throw new AccountPermissionException(SPException::INFO);
        }

        if ($userData->isAdminApp || $userData->isAdminAcc) {
            return;
        }

        if ((int)$liveAccount->getUserId() === $userData->id
            || (int)$liveAccount->getUserGroupId() === $userData->userGroupId
        ) {
            return;
        }

        $userGroups = $this->getUserGroupIds($userData->id);

        if (in_array((int)$liveAccount->getUserGroupId(), $userGroups, true)) {
            return;
        }

        if ($this->accountToUserRepository !== null) {
            /** @var AccountItem[] $users */
            $users = $this->accountToUserRepository->getUsersByAccountId($accountId)->getDataAsArray();
            foreach ($users as $userItem) {
                if ((int)$userItem->getId() === $userData->id && (int)$userItem->getIsEdit() === 1) {
                    return;
                }
            }
        }

        if ($this->accountToUserGroupRepository !== null) {
            /** @var AccountItem[] $groups */
            $groups = $this->accountToUserGroupRepository->getUserGroupsByAccountId($accountId)->getDataAsArray();
            $allowedGroups = array_merge([$userData->userGroupId], $userGroups);
            foreach ($groups as $groupItem) {
                if (in_array((int)$groupItem->getId(), $allowedGroups, true) && (int)$groupItem->getIsEdit() === 1) {
                    return;
                }
            }
        }

        throw new AccountPermissionException(SPException::INFO);
    }

    /**
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function assertCanEditDeletedSnapshot(AccountHistoryModel $snap): void
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

        if ((int)$snap->getUserId() === $userData->id
            || (int)$snap->getUserGroupId() === $userData->userGroupId
        ) {
            return;
        }

        $userGroups = $this->getUserGroupIds($userData->id);

        if (in_array((int)$snap->getUserGroupId(), $userGroups, true)) {
            return;
        }

        throw new AccountPermissionException(SPException::INFO);
    }

    /**
     * @return int[]
     * @throws ConstraintException
     * @throws QueryException
     */
    private function getUserGroupIds(int $userId): array
    {
        if ($this->userToUserGroupRepository === null) {
            return [];
        }

        $rows = $this->userToUserGroupRepository->getGroupsForUser($userId)->getDataAsArray();

        return array_values(array_map(
            static fn($row): int => (int)($row->getUserGroupId() ?? 0),
            $rows
        ));
    }
}
