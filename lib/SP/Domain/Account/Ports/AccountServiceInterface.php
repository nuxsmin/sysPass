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
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountEnrichedDto;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Adapters\AccountData;
use SP\Domain\Account\Adapters\AccountPassData;
use SP\Domain\Account\Services\AccountBulkRequest;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Account\Services\AccountRequest;
use SP\Domain\Common\Services\ServiceException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountServiceInterface
{
    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withUsersById(AccountEnrichedDto $accountDetailsResponse): AccountServiceInterface;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withUserGroupsById(AccountEnrichedDto $accountDetailsResponse): AccountServiceInterface;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function withTagsById(AccountEnrichedDto $accountDetailsResponse): AccountServiceInterface;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function incrementViewCounter(int $id): bool;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function incrementDecryptCounter(int $id): bool;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getPasswordForId(int $id): AccountPassData;

    /**
     * @param  \SP\DataModel\AccountHistoryData  $data
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function createFromHistory(AccountHistoryData $data): int;

    /**
     * @throws QueryException
     * @throws SPException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     */
    public function create(AccountRequest $accountRequest): int;

    /**
     * Devolver los datos de la clave encriptados
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getPasswordEncrypted(string $pass, ?string $masterPass = null): array;

    /**
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ConstraintException
     */
    public function getById(int $id): AccountEnrichedDto;

    /**
     * Updates external items for the account
     *
     * @param  \SP\Domain\Account\Services\AccountRequest  $accountRequest
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function update(AccountRequest $accountRequest): void;

    /**
     * Update accounts in bulk mode
     *
     * @param  \SP\Domain\Account\Services\AccountBulkRequest  $request
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function updateBulk(AccountBulkRequest $request): void;

    /**
     * @param  \SP\Domain\Account\Services\AccountRequest  $accountRequest
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function editPassword(AccountRequest $accountRequest): void;

    /**
     * Updates an already encrypted password data from a master password changing action
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePasswordMasterPass(AccountPasswordRequest $accountRequest): bool;

    /**
     * @param  int  $historyId
     * @param  int  $accountId
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function editRestore(int $historyId, int $accountId): void;

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function delete(int $id): AccountServiceInterface;

    /**
     * @param  int[]  $ids
     *
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): AccountServiceInterface;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getForUser(?int $accountId = null): array;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getLinked(int $accountId): array;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function getPasswordHistoryForId(int $id): AccountPassData;

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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getDataForLink(int $id): AccountExtData;

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAccountsPassData(): array;
}
