<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Repositories\Track;

use SP\DataModel\TrackData;
use SP\Repositories\Repository;
use SP\Storage\Database\QueryData;

/**
 * Class TrackRepository
 *
 * @package SP\Repositories\Track
 */
final class TrackRepository extends Repository
{
    /**
     * @param TrackRequest $trackRequest
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(TrackRequest $trackRequest)
    {
        $query = /** @lang SQL */
            'INSERT INTO Track SET 
            userId = ?, 
            `source` = ?, 
            `time` = UNIX_TIMESTAMP(),
            ipv4 = ?,
            ipv6 = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $trackRequest->userId,
            $trackRequest->source,
            $trackRequest->getIpv4(),
            $trackRequest->getIpv6()
        ]);
        $queryData->setOnErrorMessage(__u('Error al crear track'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * @param $id int
     *
     * @return int
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Track WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar track'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * @param $id int
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id, 
            userId, 
            `source`, 
            `time`,
            ipv4,
            ipv6 
            FROM Track 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(TrackData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al obtener track'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, 
            userId, 
            `source`, 
            `time`,
            ipv4,
            ipv6 FROM Track';

        $queryData = new QueryData();
        $queryData->setMapClassName(TrackData::class);
        $queryData->setQuery($query);
        $queryData->setOnErrorMessage(__u('Error al obtener tracks'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param TrackRequest $trackRequest
     *
     * @return \SP\Storage\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getTracksForClientFromTime(TrackRequest $trackRequest)
    {
        $query = /** @lang SQL */
            'SELECT id, userId 
            FROM Track 
            WHERE `time` >= ? 
            AND (ipv4 = ? OR ipv6 = ?) 
            AND `source` = ?';

        $queryData = new QueryData();
        $queryData->setMapClassName(TrackData::class);
        $queryData->setQuery($query);
        $queryData->setParams([
            (int)$trackRequest->time,
            $trackRequest->getIpv4(),
            $trackRequest->getIpv6(),
            $trackRequest->source
        ]);
        $queryData->setOnErrorMessage(__u('Error al obtener tracks'));

        return $this->db->doSelect($queryData);
    }
}