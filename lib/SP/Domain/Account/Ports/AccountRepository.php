<?php
/*
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

namespace SP\Domain\Account\Ports;

use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountRepository
 *
 * @package SP\Domain\Account\Ports
 */
interface AccountRepository extends Repository
{
    /**
     * Devolver el número total de cuentas
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getTotalNumAccounts(): QueryResult;

    /**
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getPasswordForId(int $accountId): QueryResult;

    /**
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getPasswordHistoryForId(int $accountId): QueryResult;

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementDecryptCounter(int $accountId): QueryResult;

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param int $accountId
     * @param Account $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function editPassword(int $accountId, Account $account): QueryResult;

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param int $accountId
     * @param EncryptedPassword $encryptedPassword
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassword(int $accountId, EncryptedPassword $encryptedPassword): QueryResult;

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param int $accountId
     * @param Account $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function restoreModified(int $accountId, Account $account): QueryResult;

    /**
     * Updates an item for bulk action
     *
     * @param int $accountId
     * @param Account $account
     * @param bool $changeOwner
     * @param bool $changeUserGroup
     *
     * @return QueryResult
     * @throws SPException
     */
    public function updateBulk(int $accountId, Account $account, bool $changeOwner, bool $changeUserGroup): QueryResult;

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementViewCounter(int $accountId): QueryResult;

    /**
     * Obtener los datos de una cuenta.
     *
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getDataForLink(int $accountId): QueryResult;

    /**
     * @param int|null $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUser(?int $accountId = null): QueryResult;

    /**
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getLinked(int $accountId): QueryResult;

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAccountsPassData(): QueryResult;

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param Account $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(Account $account): QueryResult;

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $accountId): QueryResult;

    /**
     * Updates an item
     *
     * @param int $accountId
     * @param Account $account
     * @param bool $changeOwner
     * @param bool $changeUserGroup
     *
     * @return QueryResult
     * @throws SPException
     */
    public function update(int $accountId, Account $account, bool $changeOwner, bool $changeUserGroup): QueryResult;

    /**
     * Returns the item for given id with referential data
     *
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getByIdEnriched(int $accountId): QueryResult;

    /**
     * Returns the item for given id
     *
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getById(int $accountId): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult
     */
    public function getAll(): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array $accountsId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $accountsId): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * Create an account from deleted
     *
     * @param Account $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function createRemoved(Account $account): QueryResult;
}
