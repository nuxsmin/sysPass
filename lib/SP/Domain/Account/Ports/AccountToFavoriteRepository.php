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
 * Class AccountFavoriteRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
interface AccountToFavoriteRepository extends Repository
{
    /**
     * Obtener un array con los Ids de cuentas favoritas
     *
     * @param $id int El Id de usuario
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUserId(int $id): QueryResult;

    /**
     * Añadir una cuenta a la lista de favoritos
     *
     * @param $accountId int El Id de la cuenta
     * @param $userId    int El Id del usuario
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $accountId, int $userId): int;

    /**
     * Eliminar una cuenta de la lista de favoritos
     *
     * @param $accountId int El Id de la cuenta
     * @param $userId    int El Id del usuario
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $accountId, int $userId): bool;
}
