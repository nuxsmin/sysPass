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

use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName as AccountPassItemWithIdAndNameModel;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Common\Models\Item;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Dtos\QueryResult;

/**
 * Account service facade.
 *
 * Reads are delegated to AccountReader and mutations to AccountWriter so each
 * collaborator has a single reason to change.
 */
final class Account extends Service implements AccountService
{
    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly AccountRepository $accountRepository,
        private readonly AccountToUserGroupRepository $accountToUserGroupRepository,
        private readonly AccountToUserRepository $accountToUserRepository,
        private readonly AccountToTagRepository $accountToTagRepository,
        private readonly AccountReader $accountReader,
        private readonly AccountWriter $accountWriter
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function withTags(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto
    {
        $tags = array_map(
            static fn($tag) => Item::buildFromSimpleModel($tag),
            $this->accountToTagRepository->getTagsByAccountId($accountEnrichedDto->getId())->getDataAsArray()
        );

        return $accountEnrichedDto->withTags($tags);
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
     */
    public function getPasswordForId(int $id): AccountPassItemWithIdAndNameModel
    {
        return $this->accountReader->getPasswordForId($id);
    }

    /**
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ConstraintException
     */
    public function getByIdEnriched(int $id): AccountView
    {
        return $this->accountReader->getByIdEnriched($id);
    }

    /**
     * @throws ServiceException
     */
    public function updateBulk(AccountUpdateBulkDto $accountUpdateBulkDto): void
    {
        $this->accountWriter->updateBulk($accountUpdateBulkDto);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): AccountModel
    {
        return $this->accountReader->getById($id);
    }

    /**
     * @throws QueryException
     * @throws SPException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     */
    public function create(AccountCreateDto $accountCreateDto): int
    {
        return $this->accountWriter->create($accountCreateDto);
    }

    /**
     * @throws ServiceException
     */
    public function update(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->accountWriter->update($id, $accountUpdateDto);
    }

    /**
     * @throws ServiceException
     */
    public function editPassword(int $id, AccountUpdateDto $accountUpdateDto): void
    {
        $this->accountWriter->editPassword($id, $accountUpdateDto);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function updatePasswordMasterPass(int $id, EncryptedPassword $encryptedPassword): void
    {
        $this->accountWriter->updatePasswordMasterPass($id, $encryptedPassword);
    }

    /**
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    public function restoreModified(AccountHistoryDto $dto): void
    {
        $this->accountWriter->restoreModified($dto);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function restoreRemoved(AccountHistoryDto $accountHistoryDto): void
    {
        $this->accountWriter->restoreRemoved($accountHistoryDto);
    }

    /**
     * @throws ServiceException
     */
    public function delete(int $id): AccountService
    {
        $this->accountWriter->delete($id);

        return $this;
    }

    /**
     * @param int[] $ids
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void
    {
        $this->accountWriter->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getForUser(?int $id = null): array
    {
        return $this->accountReader->getForUser($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getLinked(int $id): array
    {
        return $this->accountReader->getLinked($id);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function getPasswordHistoryForId(int $id): AccountPassItemWithIdAndNameModel
    {
        return $this->accountReader->getPasswordHistoryForId($id);
    }

    /**
     * @return AccountModel[]
     */
    public function getAllBasic(): array
    {
        return $this->accountReader->getAllBasic();
    }

    /**
     * @return array<AccountModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllForExport(): array
    {
        return $this->accountReader->getAllForExport();
    }

    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->accountReader->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getTotalNumAccounts(): int
    {
        return $this->accountReader->getTotalNumAccounts();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function getDataForLink(int $id): Simple
    {
        return $this->accountReader->getDataForLink($id);
    }

    /**
     * @return Simple[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getAccountsPassData(): array
    {
        return $this->accountReader->getAccountsPassData();
    }
}
