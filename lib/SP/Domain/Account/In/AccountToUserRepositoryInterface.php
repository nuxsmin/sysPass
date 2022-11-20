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
use SP\Domain\Account\Services\AccountRequest;
use SP\Domain\Common\In\RepositoryInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountToUserRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
interface AccountToUserRepositoryInterface extends RepositoryInterface
{
    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param  AccountRequest  $accountRequest
     *
     * @param  bool  $isEdit
     *
     * @return void
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updateByType(AccountRequest $accountRequest, bool $isEdit): void;

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param  int  $id  con el Id de la cuenta
     * @param  bool  $isEdit
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteTypeByAccountId(int $id, bool $isEdit): int;

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param  AccountRequest  $accountRequest
     * @param  bool  $isEdit
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addByType(AccountRequest $accountRequest, bool $isEdit): int;

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param  int  $id  con el Id de la cuenta
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId(int $id): int;

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param  int  $id  con el id de la cuenta
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersByAccountId(int $id): QueryResult;
}
