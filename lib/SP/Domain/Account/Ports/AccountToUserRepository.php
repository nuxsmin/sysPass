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

namespace SP\Domain\Account\Ports;

use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountToUserRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
interface AccountToUserRepository extends Repository
{
    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $id con el Id de la cuenta
     * @param bool $isEdit
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteTypeByAccountId(int $id, bool $isEdit): void;

    /**
     * Crear asociación de usuarios con cuentas.
     *
     * @param int $accountId
     * @param array $items
     * @param bool $isEdit
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addByType(int $accountId, array $items, bool $isEdit = false): void;

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $id con el Id de la cuenta
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId(int $id): bool;

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param int $id con el id de la cuenta
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersByAccountId(int $id): QueryResult;
}
