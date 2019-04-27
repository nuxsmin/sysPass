<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Repositories\User;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Repositories\Repository;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class UserPassRecoverRepository
 *
 * @package SP\Repositories\UserPassRecover
 */
final class UserPassRecoverRepository extends Repository
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
    public function getAttemptsByUserId($userId, $time)
    {
        $query = /** @lang SQL */
            'SELECT userId 
            FROM UserPassRecover
            WHERE userId = ?
            AND used = 0
            AND `date` >= ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([(int)$userId, (int)$time]);

        return $this->db->doSelect($queryData)->getNumRows();
    }

    /**
     * Adds a hash for a user's id
     *
     * @param int    $userId
     * @param string $hash
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add($userId, $hash)
    {
        $query = /** @lang SQL */
            'INSERT INTO UserPassRecover SET 
            userId = ?,
            `hash` = ?,
            `date` = UNIX_TIMESTAMP(),
            used = 0';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([(int)$userId, $hash]);
        $queryData->setOnErrorMessage(__u('Error while generating the recovering hash'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Toggles a hash used
     *
     * @param string $hash
     * @param int    $time
     *
     * @return int
     * @throws SPException
     */
    public function toggleUsedByHash($hash, $time)
    {
        $query = /** @lang SQL */
            'UPDATE UserPassRecover SET used = 1 
            WHERE `hash` = ?
            AND used = 0
            AND `date` >= ? 
            LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$hash, (int)$time]);
        $queryData->setOnErrorMessage(__u('Error while checking hash'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param $hash
     * @param $time
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserIdForHash($hash, $time)
    {
        $query = /** @lang SQL */
            'SELECT userId
            FROM UserPassRecover
            WHERE `hash` = ?
            AND used = 0
            AND `date` >= ? 
            ORDER BY `date` DESC LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$hash, (int)$time]);

        return $this->db->doSelect($queryData);
    }
}