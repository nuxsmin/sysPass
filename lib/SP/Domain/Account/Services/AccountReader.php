<?php

declare(strict_types=1);
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

namespace SP\Domain\Account\Services;

use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName as AccountPassItemWithIdAndNameModel;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountHistoryRepository;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Dtos\QueryResult;

/**
 * Reads accounts from the repository and maps them to domain models.
 */
final readonly class AccountReader
{
    public function __construct(
        private AccountRepository             $accountRepository,
        private ?AccountAclService            $accountAclService,
        private ?AccountToUserRepository      $accountToUserRepository,
        private ?AccountToUserGroupRepository $accountToUserGroupRepository,
        private ?AclInterface                 $acl = null,
        private ?AccountHistoryRepository     $accountHistoryRepository = null
    ) {
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): AccountModel
    {
        $result = $this->accountRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('The account doesn\'t exist');
        }

        return $result->getData(AccountModel::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByIdEnriched(int $id): AccountView
    {
        $result = $this->accountRepository->getByIdEnriched($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('The account doesn\'t exist');
        }

        return $result->getData(AccountView::class);
    }

    /**
     * Checks whether the account is visible to the current user
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function isVisible(int $id, bool $useGlobalSearch = false): bool
    {
        return $this->accountRepository->isVisible($id, $useGlobalSearch);
    }

    /**
     * Checks whether the current user can edit/mutate the account
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function canEdit(int $id): bool
    {
        if (!$this->isVisible($id)) {
            return false;
        }

        if ($this->accountAclService === null) {
            return true;
        }

        try {
            $account = $this->getById($id);
        } catch (NoSuchItemException) {
            return false;
        }

        $users = $this->accountToUserRepository?->getUsersByAccountId($id)->getDataAsArray() ?? [];
        $userGroups = $this->accountToUserGroupRepository?->getUserGroupsByAccountId($id)->getDataAsArray() ?? [];

        $aclDto = new AccountAclDto(
            $id,
            (int)$account->getUserId(),
            $users,
            (int)$account->getUserGroupId(),
            $userGroups,
            (int)(strtotime($account->getDateEdit() ?? '') ?: 0),
            (bool)$account->getIsPrivate(),
            (bool)$account->getIsPrivateGroup()
        );

        return $this->accountAclService
            ->getAcl(AclActionsInterface::ACCOUNT_EDIT, $aclDto)
            ->checkAccountAccess(AclActionsInterface::ACCOUNT_EDIT);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws AccountPermissionException
     */
    public function getPasswordForId(int $id): AccountPassItemWithIdAndNameModel
    {
        if ($this->acl !== null && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS)) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $result = $this->accountRepository->getPasswordForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('Account not found');
        }

        /** @var AccountPassItemWithIdAndNameModel $account */
        $account = $result->getData(AccountPassItemWithIdAndNameModel::class);
        $parentId = (int)($account->getParentId() ?? 0);

        if ($parentId > 0) {
            $parent = $this->resolveLinkedParentPassword($id, $parentId);

            $account = $account->mutate([
                'pass' => $parent->getPass(),
                'key' => $parent->getKey(),
            ]);
        }

        return $account;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws AccountPermissionException
     */
    public function getPasswordHistoryForId(int $id): AccountPassItemWithIdAndNameModel
    {
        if ($this->acl !== null && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_HISTORY_VIEW_PASS)) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $result = $this->accountRepository->getPasswordHistoryForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('The account doesn\'t exist');
        }

        /** @var AccountPassItemWithIdAndNameModel $account */
        $account = $result->getData(AccountPassItemWithIdAndNameModel::class);

        $accountId = (int)($account->getAccountId() ?? 0);

        if ($this->accountHistoryRepository !== null) {
            if ($accountId <= 0) {
                $historyResult = $this->accountHistoryRepository->getById($id);
                if ($historyResult->getNumRows() === 0) {
                    throw new NoSuchItemException('The account doesn\'t exist');
                }
                /** @var AccountHistoryModel $historyRow */
                $historyRow = $historyResult->getData(AccountHistoryModel::class);
                $accountId = (int)$historyRow->getAccountId();
            }

            if ($accountId <= 0) {
                throw new AccountPermissionException(SPException::INFO);
            }

            $liveResult = $this->accountRepository->getById($accountId);

            if ($liveResult->getNumRows() > 0) {
                if (!$this->isVisible($accountId)) {
                    throw new AccountPermissionException(SPException::INFO);
                }
            } else {
                /** @var AccountHistoryModel[] $allRows */
                $allRows = $this->accountHistoryRepository
                    ->getByAccountId($accountId)
                    ->getDataAsArray(AccountHistoryModel::class);
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

                if ($this->accountAclService !== null) {
                    $aclDto = new AccountAclDto(
                        $accountId,
                        (int)$snap->getUserId(),
                        [],
                        (int)$snap->getUserGroupId(),
                        [],
                        null,
                        (bool)$snap->getIsPrivate(),
                        (bool)$snap->getIsPrivateGroup()
                    );

                    if (!$this->accountAclService
                        ->getAcl(AclActionsInterface::ACCOUNT_VIEW, $aclDto, true)
                        ->checkAccountAccess(AclActionsInterface::ACCOUNT_VIEW)
                    ) {
                        throw new AccountPermissionException(SPException::INFO);
                    }
                }
            }
        }

        $parentId = (int)($account->getParentId() ?? 0);

        if ($parentId > 0) {
            $parent = $this->resolveLinkedParentPassword($accountId, $parentId);

            $account = $account->mutate([
                'pass' => $parent->getPass(),
                'key' => $parent->getKey(),
            ]);
        }

        return $account;
    }

    /**
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
     * @return AccountModel[]
     */
    public function getAllBasic(): array
    {
        return $this->accountRepository->getAll()->getDataAsArray();
    }

    /**
     * @return array<AccountModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllForExport(): array
    {
        return $this->accountRepository->getAllForExport()->getDataAsArray();
    }

    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->accountRepository->search($itemSearchData);
    }

    /**
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws AccountPermissionException
     * @throws SPException
     */
    public function getDataForLink(int $id): Simple
    {
        $result = $this->accountRepository->getDataForLink($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('The account doesn\'t exist');
        }

        /** @var Simple $data */
        $data = $result->getData(Simple::class);
        $parentId = (int)($data['parentId'] ?? 0);

        if ($parentId > 0) {
            if ($this->acl !== null && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS)) {
                throw new AccountPermissionException(SPException::INFO);
            }

            $parent = $this->resolveLinkedParentPassword($id, $parentId);

            $data = $data->mutate([
                'pass' => $parent->getPass(),
                'key' => $parent->getKey(),
            ]);
        }

        return $data;
    }

    /**
     * @return Simple[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getAccountsPassData(): array
    {
        return $this->accountRepository->getAccountsPassData()->getDataAsArray(Simple::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws AccountPermissionException
     */
    private function resolveLinkedParentPassword(int $accountId, int $parentId): AccountPassItemWithIdAndNameModel
    {
        if ($parentId === $accountId) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $parentResult = $this->accountRepository->getPasswordForId($parentId);

        if ($parentResult->getNumRows() === 0) {
            throw new AccountPermissionException(SPException::INFO);
        }

        /** @var AccountPassItemWithIdAndNameModel $parent */
        $parent = $parentResult->getData(AccountPassItemWithIdAndNameModel::class);

        if ((int)($parent->getParentId() ?? 0) > 0
            || (string)($parent->getPass() ?? '') === ''
            || (string)($parent->getKey() ?? '') === ''
        ) {
            throw new AccountPermissionException(SPException::INFO);
        }

        return $parent;
    }
}
