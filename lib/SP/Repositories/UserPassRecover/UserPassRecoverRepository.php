<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\UserPassRecover;

use SP\Core\Exceptions\SPException;
use SP\Repositories\Repository;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserPassRecoverRepository
 *
 * @package SP\Repositories\UserPassRecover
 */
class UserPassRecoverRepository extends Repository
{
    /**
     * Checks recovery limit attempts by user's id and time
     *
     * @param int $userId
     * @param int $time
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
        $queryData->addParam($userId);
        $queryData->addParam($time);

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows();
    }

    /**
     * Adds a hash for a user's id
     *
     * @param $userId
     * @param $hash
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
        $queryData->addParam($userId);
        $queryData->addParam($hash);
        $queryData->setOnErrorMessage(__u('Error al generar el hash de recuperación'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Toggles a hash used
     *
     * @param string $hash
     * @param int    $time
     * @return bool
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
        $queryData->addParam($hash);
        $queryData->addParam($time);
        $queryData->setOnErrorMessage(__u('Error en comprobación de hash'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param $hash
     * @param $time
     * @return mixed
     */
    public function getUserIdForHash($hash, $time)
    {
        $query = /** @lang SQL */
            'SELECT userId
            FROM UserPassRecover
            WHERE `hash` = ?
            AND used = 0
            AND `date` >= ? 
            ORDER BY date DESC LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($hash);
        $queryData->addParam($time);

        return DbWrapper::getResults($queryData, $this->db);
    }
}