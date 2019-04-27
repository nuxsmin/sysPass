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

namespace SP\Repositories\Track;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TrackData;
use SP\Repositories\Repository;
use SP\Services\Track\TrackService;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

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
     * @throws ConstraintException
     * @throws QueryException
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
        $queryData->setOnErrorMessage(__u('Error while creating track'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * @param $id int
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Track WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while removing the track'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * @param $id int
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function unlock($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Track SET timeUnlock = UNIX_TIMESTAMP() WHERE id = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while updating the track'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Clears tracks
     *
     * @return bool con el resultado
     * @throws QueryException
     * @throws ConstraintException
     */
    public function clear()
    {
        $queryData = new QueryData();
        $queryData->setQuery('TRUNCATE TABLE Track');
        $queryData->setOnErrorMessage(__u('Error while clearing tracks out'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() > 0;
    }

    /**
     * @param $id int
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
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
        $queryData->setOnErrorMessage(__u('Error while retrieving the track'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
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
        $queryData->setOnErrorMessage(__u('Error while retrieving the tracks'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param TrackRequest $trackRequest
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTracksForClientFromTime(TrackRequest $trackRequest)
    {
        $query = /** @lang SQL */
            'SELECT id, userId 
            FROM Track 
            WHERE `time` >= ? 
            AND (ipv4 = ? OR ipv6 = ?) 
            AND `source` = ?
            AND timeUnlock IS NULL';

        $queryData = new QueryData();
        $queryData->setMapClassName(TrackData::class);
        $queryData->setQuery($query);
        $queryData->setParams([
            (int)$trackRequest->time,
            $trackRequest->getIpv4(),
            $trackRequest->getIpv6(),
            $trackRequest->source
        ]);
        $queryData->setOnErrorMessage(__u('Error while retrieving the tracks'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setSelect('
        id, 
        userId, 
        source, 
        time, 
        timeUnlock, 
        FROM_UNIXTIME(time) as dateTime, 
        FROM_UNIXTIME(timeUnlock) as dateTimeUnlock, 
        ipv4, 
        ipv6, 
        IF(`time` >= ? AND `timeUnlock` IS NULL, 1, 0) AS tracked
        ');
        $queryData->addParam(time() - TrackService::TIME_TRACKING);

        $queryData->setFrom('Track');
        $queryData->setOrder('time DESC');

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere('source LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }
}