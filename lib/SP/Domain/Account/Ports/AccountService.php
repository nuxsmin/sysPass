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

namespace SP\Domain\Account\Ports;

use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountService
{
    /**
     * @param AccountEnrichedDto $accountEnrichedDto
     *
     * @return AccountEnrichedDto
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withUsers(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto;

    /**
     * @param AccountEnrichedDto $accountEnrichedDto
     *
     * @return AccountEnrichedDto
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withUserGroups(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto;

    /**
     * @param AccountEnrichedDto $accountEnrichedDto
     *
     * @return AccountEnrichedDto
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withTags(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto;

    /**
     * @param int $id The account ID
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementViewCounter(int $id): bool;

    /**
     * @param int $id The account ID
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function incrementDecryptCounter(int $id): bool;

    /**
     * @param int $id The account ID
     *
     * @return Account
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getPasswordForId(int $id): Account;

    /**
     * @param AccountHistoryDto $accountHistoryDto
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function restoreRemoved(AccountHistoryDto $accountHistoryDto): void;

    /**
     * @param AccountCreateDto $accountCreateDto
     *
     * @return int
     * @throws QueryException
     * @throws SPException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     */
    public function create(AccountCreateDto $accountCreateDto): int;

    /**
     * @param int $id The account ID
     *
     * @return AccountView
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ConstraintException
     */
    public function getByIdEnriched(int $id): AccountView;

    /**
     * @param int $id The account ID
     *
     * @return Account
     * @throws NoSuchItemException
     */
    public function getById(int $id): Account;

    /**
     * Updates external items for the account
     *
     * @param int $id The account ID
     * @param AccountUpdateDto $accountUpdateDto
     *
     * @throws ServiceException
     */
    public function update(int $id, AccountUpdateDto $accountUpdateDto): void;

    /**
     * Update accounts in bulk mode
     *
     * @param AccountUpdateBulkDto $accountUpdateBulkDto
     *
     * @throws ServiceException
     */
    public function updateBulk(AccountUpdateBulkDto $accountUpdateBulkDto): void;

    /**
     * @param int $id The account ID
     * @param AccountUpdateDto $accountUpdateDto
     *
     * @throws ServiceException
     */
    public function editPassword(int $id, AccountUpdateDto $accountUpdateDto): void;

    /**
     * Updates an already encrypted password data from a master password changing action
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePasswordMasterPass(int $id, EncryptedPassword $encryptedPassword): void;

    /**
     * @param AccountHistoryDto $accountHistoryDto
     *
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    public function restoreModified(AccountHistoryDto $accountHistoryDto): void;

    /**
     * @param int $id The account ID
     *
     * @return AccountService
     * @throws ServiceException
     */
    public function delete(int $id): AccountService;

    /**
     * @param int[] $ids The accounts ID
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void;

    /**
     * @param int|null $id The account ID
     *
     * @return array
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getForUser(?int $id = null): array;

    /**
     * @param int $id The account ID
     *
     * @return array
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getLinked(int $id): array;

    /**
     * @param int $id The account ID
     *
     * @return Simple
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function getPasswordHistoryForId(int $id): Simple;

    /**
     * @return Account[]
     */
    public function getAllBasic(): array;

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * Devolver el número total de cuentas
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTotalNumAccounts(): int;

    /**
     * Obtener los datos de una cuenta.
     *
     * @param int $id The account ID
     *
     * @return Simple
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getDataForLink(int $id): Simple;

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAccountsPassData(): array;
}
