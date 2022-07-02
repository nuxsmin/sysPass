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

namespace SP\Domain\Account\In;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Account\Services\AccountRequest;
use SP\Domain\Account\Services\AccountSearchFilter;
use SP\Domain\Common\In\RepositoryInterface;
use SP\Domain\Common\Out\SimpleModel;
use SP\Infrastructure\Database\QueryResult;
use SP\Mvc\Model\QueryCondition;

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
     * @param  QueryCondition  $queryCondition
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getPasswordForId(int $id, QueryCondition $queryCondition): QueryResult;

    /**
     * @param  QueryCondition  $queryCondition
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getPasswordHistoryForId(QueryCondition $queryCondition): QueryResult;

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
     * @param  AccountRequest  $accountRequest
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
     * @param  int  $historyId  El Id del registro en el histórico
     * @param  int  $userId  User's Id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function editRestore(int $historyId, int $userId): bool;

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
     * Obtener las cuentas de una búsqueda.
     *
     * @param  AccountSearchFilter  $accountSearchFilter
     * @param  QueryCondition  $queryFilterUser
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter, QueryCondition $queryFilterUser): QueryResult;

    /**
     * @param  QueryCondition  $queryFilter
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUser(QueryCondition $queryFilter): QueryResult;

    /**
     * @param  QueryCondition  $queryFilter
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getLinked(QueryCondition $queryFilter): QueryResult;

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return \SP\Infrastructure\Database\QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAccountsPassData(): QueryResult;
}