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

namespace SP\Repositories\EventLog;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\EventlogData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class EventlogRepository
 *
 * @package SP\Repositories\EventLog
 */
final class EventlogRepository extends Repository
{
    /**
     * Clears the event log
     *
     * @return bool con el resultado
     * @throws QueryException
     * @throws ConstraintException
     */
    public function clear()
    {
        $queryData = new QueryData();
        $queryData->setQuery('TRUNCATE TABLE EventLog');
        $queryData->setOnErrorMessage(__u('Error while clearing the event log out'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() > 0;
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
        $queryData->setSelect('id, FROM_UNIXTIME(date) AS date, action, level, login, ipAddress, description');
        $queryData->setFrom('EventLog');
        $queryData->setOrder('id DESC');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('action LIKE ? OR login LIKE ? OR ipAddress LIKE ? OR description LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->setParams(array_fill(0, 4, $search));
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }


    /**
     * @param EventlogData $eventlogData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(EventlogData $eventlogData)
    {
        $sql = 'INSERT INTO EventLog SET
                `date` = UNIX_TIMESTAMP(),
                login = ?,
                userId = ?,
                ipAddress = ?,
                `action` = ?,
                description = ?,
                `level` = ?';

        $queryData = new QueryData();
        $queryData->setQuery($sql);
        $queryData->setParams([
                $eventlogData->getLogin(),
                $eventlogData->getUserId(),
                $eventlogData->getIpAddress(),
                $eventlogData->getAction(),
                $eventlogData->getDescription(),
                $eventlogData->getLevel()]
        );

        return $this->db->doQuery($queryData)->getLastId();
    }
}