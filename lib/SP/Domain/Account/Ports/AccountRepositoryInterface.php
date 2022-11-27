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
use SP\DataModel\AccountHistoryData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Common\Adapters\SimpleModel;
use SP\Domain\Common\Ports\RepositoryInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountRepository
 *
 * @package Services
 */
interface AccountRepositoryInterface extends RepositoryInterface
{
    /**
     * Devolver el número total de cuentas
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getTotalNumAccounts(): SimpleModel;

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getPasswordForId(int $id): QueryResult;

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getPasswordHistoryForId(int $id): QueryResult;

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param  int  $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementDecryptCounter(int $id): bool;

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $accountRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function editPassword(AccountRequest $accountRequest): int;

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param  AccountPasswordRequest  $request
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassword(AccountPasswordRequest $request): bool;

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param  \SP\DataModel\AccountHistoryData  $accountHistoryData
     * @param  int  $userId  User's Id
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function editRestore(AccountHistoryData $accountHistoryData, int $userId): bool;

    /**
     * Updates an item for bulk action
     *
     * @param  AccountRequest  $itemData
     *
     * @return int
     * @throws SPException
     */
    public function updateBulk(AccountRequest $itemData): int;

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @param  int  $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementViewCounter(int $id): bool;

    /**
     * Obtener los datos de una cuenta.
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getDataForLink(int $id): QueryResult;

    /**
     * @param  int|null  $accountId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getForUser(?int $accountId = null): QueryResult;

    /**
     * @param  int  $accountId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getLinked(int $accountId): QueryResult;

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return \SP\Infrastructure\Database\QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAccountsPassData(): QueryResult;

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $accountRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AccountRequest $accountRequest): int;

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param  int  $id
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): bool;

    /**
     * Updates an item
     *
     * @param  AccountRequest  $accountRequest
     *
     * @return int
     * @throws SPException
     */
    public function update(AccountRequest $accountRequest): int;

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getById(int $id): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult
     */
    public function getAll(): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

}
