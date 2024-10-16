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

namespace SP\Infrastructure\Account\Repositories;

use SP\Domain\Account\Ports\AccountToFavoriteRepository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AccountToFavorite
 */
final class AccountToFavorite extends BaseRepository implements AccountToFavoriteRepository
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
    public function getForUserId(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                'accountId',
                'userId',
            ])
            ->from('AccountToFavorite')
            ->where('userId = :userId')
            ->bindValues(['userId' => $id]);

        return $this->db->runQuery(QueryData::build($query));
    }

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
    public function add(int $accountId, int $userId): int
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into('AccountToFavorite')
            ->cols([
                'accountId' => $accountId,
                'userId'    => $userId,
            ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while adding favorite'));

        return $this->db->runQuery($queryData)->getLastId();
    }

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
    public function delete(int $accountId, int $userId): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountToFavorite')
            ->where('accountId = :accountId')
            ->where('userId = :userId')
            ->bindValues([
                'accountId' => $accountId,
                'userId'    => $userId,
            ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting favorite'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }
}
