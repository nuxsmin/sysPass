<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\UserGroup;

use SP\Core\Acl\Acl;
use SP\Core\Exceptions\SPException;
use SP\DataModel\GroupData;
use SP\DataModel\ItemSearchData;
use SP\Log\Log;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserGroupService
 *
 * @package SP\Services\UserGroup
 */
class UserGroupService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * Deletes an item
     *
     * @param $id
     * @return mixed
     * @throws SPException
     */
    public function delete($id)
    {
        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_WARNING, __u('Grupo en uso'));
        }

        $query = /** @lang SQL */
            'DELETE FROM usrGroups WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el grupo'));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Grupo no encontrado'));
        }

        return $this;
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT user_groupId AS groupId
            FROM usrData WHERE user_groupId = ?
            UNION ALL
            SELECT usertogroup_groupId AS groupId
            FROM usrToGroups WHERE usertogroup_groupId = ?
            UNION ALL
            SELECT accgroup_groupId AS groupId
            FROM accGroups WHERE accgroup_groupId = ?
            UNION ALL
            SELECT account_userGroupId AS groupId
            FROM accounts WHERE account_userGroupId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($id);
        $Data->addParam($id);
        $Data->addParam($id);

        DbWrapper::getQuery($Data);

        return ($Data->getQueryNumRows() > 1);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_id, usergroup_name, usergroup_description FROM usrGroups WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(GroupData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data);
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT usergroup_id,
            usergroup_name,
            usergroup_description
            FROM usrGroups
            ORDER BY usergroup_name';

        $Data = new QueryData();
        $Data->setMapClassName(GroupData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT usergroup_id, usergroup_name, usergroup_description FROM usrGroups WHERE usergroup_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(GroupData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return UserGroupService
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'DELETE FROM usrGroups WHERE usergroup_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(GroupData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        DbWrapper::getQuery($Data);

        return $this;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(GroupData::class);
        $Data->setSelect('usergroup_id, usergroup_name, usergroup_description');
        $Data->setFrom('usrGroups');
        $Data->setOrder('usergroup_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('usergroup_name LIKE ? OR usergroup_description LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Creates an item
     *
     * @param mixed $itemData
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Nombre de grupo duplicado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO usrGroups SET usergroup_name = ?, usergroup_description = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUsergroupName());
        $Data->addParam($itemData->getUsergroupDescription());
        $Data->setOnErrorMessage(__u('Error al crear el grupo'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_name FROM usrGroups WHERE UPPER(usergroup_name) = ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUsergroupName());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param mixed $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Nombre de grupo duplicado'));
        }

        $query = /** @lang SQL */
            'UPDATE usrGroups SET usergroup_name = ?, usergroup_description = ? WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUsergroupName());
        $Data->addParam($itemData->getUsergroupDescription());
        $Data->addParam($itemData->getUsergroupId());
        $Data->setOnErrorMessage(__u('Error al actualizar el grupo'));

        DbWrapper::getQuery($Data);

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_name FROM usrGroups WHERE UPPER(usergroup_name) = ? AND usergroup_id <> ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUsergroupName());
        $Data->addParam($itemData->getUsergroupId());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Logs group action
     *
     * @param int $id
     * @param int $actionId
     * @return \SP\Core\Messages\LogMessage
     */
    public function logAction($id, $actionId)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_name FROM usrGroups WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $usergroup = DbWrapper::getResults($Data, $this->db);

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(Acl::getActionInfo($actionId));
        $LogMessage->addDetails(__u('Grupo'), $usergroup->usergroup_name);
        $LogMessage->addDetails(__u('ID'), $id);
        $Log->writeLog();

        return $LogMessage;
    }
}