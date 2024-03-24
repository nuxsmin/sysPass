<?php
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

namespace SP\Infrastructure\Security\Repositories;

use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Security\Models\Track as TrackModel;
use SP\Domain\Security\Ports\TrackRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class Track
 *
 * @template T of TrackModel
 */
final class Track extends BaseRepository implements TrackRepository
{
    public const TABLE = 'Track';

    /**
     * @param TrackModel $track
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackModel $track): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols($track->toArray(null, ['id', 'time']))
            ->set('time', 'UNIX_TIMESTAMP()');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating track'));

        return $this->db->doQuery($queryData);
    }

    /**
     * @param $id int
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function unlock(int $id): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->set('timeUnlock', 'UNIX_TIMESTAMP()')
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the track'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Clears tracks
     *
     * @return bool con el resultado
     * @throws QueryException
     * @throws ConstraintException
     */
    public function clear(): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while clearing the tracks out'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() > 0;
    }

    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param TrackModel $track
     *
     * @return QueryResult<T>
     */
    public function getTracksForClientFromTime(TrackModel $track): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(TrackModel::getCols())
            ->where('time >= :time AND (ipv4 = :ipv4 OR ipv6 = :ipv6) AND source = :source AND timeUnlock IS NULL')
            ->bindValues([
                             'time' => $track->getTime(),
                             'ipv4' => $track->getIpv4(),
                             'ipv6' => $track->getIpv6(),
                             'source' => $track->getSource()
                         ])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, TrackModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @return QueryResult<T>
     */
    public function search(ItemSearchData $itemSearchData, int $time): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(TrackModel::getCols())
            ->cols([
                       'FROM_UNIXTIME(time)' => 'dateTime',
                       'FROM_UNIXTIME(timeUnlock)' => 'dateTimeUnlock',
                       'IF(time >= :time AND timeUnlock IS NULL, 1, 0)' => 'tracked'
                   ])
            ->orderBy(['time DESC'])
            ->bindValues(['time' => $time])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('source LIKE :source');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['source' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(TrackModel::class);

        return $this->db->doSelect($queryData, true);
    }
}
