<?php
declare(strict_types=1);
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

namespace SP\Infrastructure\User\Repositories;

use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\UserPassRecover as UserPassRecoverModel;
use SP\Domain\User\Ports\UserPassRecoverRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class UserPassRecover
 *
 * @template T of UserPassRecoverModel
 */
final class UserPassRecover extends BaseRepository implements UserPassRecoverRepository
{
    /**
     * Checks recovery limit attempts by user's id and time
     *
     * @param int $userId
     * @param int $time
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAttemptsByUserId(int $userId, int $time): int
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserPassRecoverModel::TABLE)
            ->cols(['date'])
            ->where('userId = :userId')
            ->where('used = 0')
            ->where('date >= :date')
            ->bindValues(['userId' => $userId, 'date' => $time]);

        return $this->db->runQuery(QueryData::build($query))->getNumRows();
    }

    /**
     * Adds a hash for a user's id
     *
     * @param int $userId
     * @param string $hash
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $userId, string $hash): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(UserPassRecoverModel::TABLE)
            ->cols(['userId' => $userId, 'hash' => $hash])
            ->set('date', 'UNIX_TIMESTAMP()')
            ->set('used', 0);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while generating the recovering hash'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Toggles a hash used
     *
     * @param string $hash
     * @param int $time
     *
     * @return int
     * @throws SPException
     */
    public function toggleUsedByHash(string $hash, int $time): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserPassRecoverModel::TABLE)
            ->cols(['used' => 1])
            ->where('hash = :hash', ['hash' => $hash])
            ->where('date >= :date', ['date' => $time])
            ->where('used = 0')
            ->limit(1);

        $queryData = QueryData::build($query);
        $queryData->setOnErrorMessage(__u('Error while checking hash'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param string $hash
     * @param int $time
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserIdForHash(string $hash, int $time): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserPassRecoverModel::getCols())
            ->from(UserPassRecoverModel::TABLE)
            ->where('hash = :hash')
            ->where('used = 0')
            ->where('date >= :date')
            ->orderBy(['date DESC'])
            ->limit(1)
            ->bindValues(
                [
                    'hash' => $hash,
                    'date' => $time
                ]
            );

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(UserPassRecoverModel::class));
    }
}
