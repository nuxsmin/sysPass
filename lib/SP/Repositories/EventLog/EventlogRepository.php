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

namespace SP\Repositories\EventLog;

use SP\DataModel\EventlogData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class EventlogRepository
 *
 * @package SP\Repositories\EventLog
 */
class EventlogRepository extends Repository
{
    /**
     * Clears the event log
     *
     * @return bool con el resultado
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function clear()
    {
        $queryData = new QueryData();
        $queryData->setQuery('TRUNCATE TABLE EventLog');
        $queryData->setOnErrorMessage(__u('Error al vaciar el registro de eventos'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setSelect('id,FROM_UNIXTIME(date) AS date,action,level,login,ipAddress,description');
        $queryData->setFrom('EventLog');
        $queryData->setOrder('id DESC');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('action LIKE ? OR ipAddress LIKE ? OR description LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($itemSearchData->getLimitStart());
        $queryData->addParam($itemSearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        /** @var array $queryRes */
        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        $queryRes['count'] = $queryData->getQueryNumRows();

        return $queryRes;
    }


    /**
     * @param EventlogData $eventlogData
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
        $queryData->addParam($eventlogData->getLogin());
        $queryData->addParam($eventlogData->getUserId());
        $queryData->addParam($eventlogData->getIpAddress());
        $queryData->addParam($eventlogData->getAction());
        $queryData->addParam($eventlogData->getDescription());
        $queryData->addParam($eventlogData->getLevel());

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }
}