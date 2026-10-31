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
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Common\Models\Simple;
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
    public function __construct(private AccountRepository $accountRepository)
    {
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getPasswordForId(int $id): AccountPassItemWithIdAndNameModel
    {
        $result = $this->accountRepository->getPasswordForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('Account not found');
        }

        return $result->getData(AccountPassItemWithIdAndNameModel::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getPasswordHistoryForId(int $id): AccountPassItemWithIdAndNameModel
    {
        $result = $this->accountRepository->getPasswordHistoryForId($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('The account doesn\'t exist');
        }

        return $result->getData(AccountPassItemWithIdAndNameModel::class);
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
     * @throws SPException
     */
    public function getDataForLink(int $id): Simple
    {
        $result = $this->accountRepository->getDataForLink($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException('The account doesn\'t exist');
        }

        return $result->getData(Simple::class);
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
}
