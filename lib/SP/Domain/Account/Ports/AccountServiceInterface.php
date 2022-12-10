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

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Adapters\AccountData;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Models\AccountDataView;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\ServiceException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountServiceInterface
{
    /**
     * @param  \SP\Domain\Account\Dtos\AccountEnrichedDto  $accountEnrichedDto
     *
     * @return \SP\Domain\Account\Dtos\AccountEnrichedDto
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function withUsers(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto;

    /**
     * @param  \SP\Domain\Account\Dtos\AccountEnrichedDto  $accountEnrichedDto
     *
     * @return \SP\Domain\Account\Dtos\AccountEnrichedDto
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function withUserGroups(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto;

    /**
     * @param  \SP\Domain\Account\Dtos\AccountEnrichedDto  $accountEnrichedDto
     *
     * @return \SP\Domain\Account\Dtos\AccountEnrichedDto
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function withTags(AccountEnrichedDto $accountEnrichedDto): AccountEnrichedDto;

    /**
     * @param  int  $id  The account ID
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function incrementViewCounter(int $id): bool;

    /**
     * @param  int  $id  The account ID
     *
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementDecryptCounter(int $id): bool;

    /**
     * @param  int  $id  The account ID
     *
     * @return \SP\Domain\Account\Models\Account
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getPasswordForId(int $id): Account;

    /**
     * @param  \SP\Domain\Account\Dtos\AccountHistoryDto  $accountHistoryDto
     *
     * @return void
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function restoreRemoved(AccountHistoryDto $accountHistoryDto): void;

    /**
     * @param  \SP\Domain\Account\Dtos\AccountCreateDto  $accountCreateDto
     *
     * @return int
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     */
    public function create(AccountCreateDto $accountCreateDto): int;

    /**
     * @param  int  $id  The account ID
     *
     * @return \SP\Domain\Account\Models\AccountDataView
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function getByIdEnriched(int $id): AccountDataView;

    /**
     * @param  int  $id  The account ID
     *
     * @return \SP\Domain\Account\Models\Account
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getById(int $id): Account;

    /**
     * Updates external items for the account
     *
     * @param  int  $id  The account ID
     * @param  \SP\Domain\Account\Dtos\AccountUpdateDto  $accountUpdateDto
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function update(int $id, AccountUpdateDto $accountUpdateDto): void;

    /**
     * Update accounts in bulk mode
     *
     * @param  \SP\Domain\Account\Dtos\AccountUpdateBulkDto  $accountUpdateBulkDto
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function updateBulk(AccountUpdateBulkDto $accountUpdateBulkDto): void;

    /**
     * @param  int  $id  The account ID
     * @param  \SP\Domain\Account\Dtos\AccountUpdateDto  $accountUpdateDto
     *
     * @throws \SP\Domain\Common\Services\ServiceException
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
     * @param  \SP\Domain\Account\Dtos\AccountHistoryDto  $accountHistoryDto
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function restoreModified(AccountHistoryDto $accountHistoryDto): void;

    /**
     * @param  int  $id  The account ID
     *
     * @return \SP\Domain\Account\Ports\AccountServiceInterface
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function delete(int $id): AccountServiceInterface;

    /**
     * @param  int[]  $ids  The accounts ID
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void;

    /**
     * @param  int|null  $id  The account ID
     *
     * @return array
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function getForUser(?int $id = null): array;

    /**
     * @param  int  $id  The account ID
     *
     * @return array
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function getLinked(int $id): array;

    /**
     * @param  int  $id  The account ID
     *
     * @return \SP\Domain\Common\Models\Simple
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getPasswordHistoryForId(int $id): Simple;

    /**
     * @return AccountData[]
     */
    public function getAllBasic(): array;

    /**
     * @param  \SP\DataModel\ItemSearchData  $itemSearchData
     *
     * @return \SP\Infrastructure\Database\QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * Devolver el número total de cuentas
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getTotalNumAccounts(): int;

    /**
     * Obtener los datos de una cuenta.
     *
     * @param  int  $id  The account ID
     *
     * @return \SP\Domain\Common\Models\Simple
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
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
