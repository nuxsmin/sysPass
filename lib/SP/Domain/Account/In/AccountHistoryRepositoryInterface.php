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
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Common\In\RepositoryInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountHistoryRepository
 *
 * @package Services
 */
interface AccountHistoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param $id
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getHistoryForAccount($id): QueryResult;

    /**
     * Deletes all the items for given accounts id
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountIdBatch(array $ids): int;

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAccountsPassData(): QueryResult;

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param  AccountPasswordRequest  $request
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassword(AccountPasswordRequest $request): int;
}