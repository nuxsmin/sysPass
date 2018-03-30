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

namespace SP\Repositories\UserGroup;

use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserGroupRepository
 *
 * @package SP\Repositories\UserGroup
 */
class UserGroupRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Deletes an item
     *
     * @param $id
     * @return int
     * @throws SPException
     */
    public function delete($id)
    {
        if ($this->checkInUse($id)) {
            throw new SPException(__u('Grupo en uso'), SPException::WARNING);
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserGroup WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar el grupo'));

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows();
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
            'SELECT userGroupId
            FROM User WHERE userGroupId = ?
            UNION ALL
            SELECT userGroupId
            FROM Account WHERE userGroupId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([(int)$id, (int)$id]);

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Returns the items that are using the given group id
     *
     * @param $id int
     * @return array
     */
    public function getUsage($id)
    {
        $query = /** @lang SQL */
            'SELECT userGroupId, "User" AS ref
            FROM User WHERE userGroupId = ?
            UNION ALL
            SELECT userGroupId, "UserGroup" AS ref
            FROM UserToUserGroup WHERE userGroupId = ?
            UNION ALL
            SELECT userGroupId, "AccountToUserGroup" AS ref
            FROM AccountToUserGroup WHERE userGroupId = ?
            UNION ALL
            SELECT userGroupId, "Account" AS ref
            FROM Account WHERE userGroupId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParams(array_fill(0, 4, (int)$id));

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns the users that are using the given group id
     *
     * @param $id int
     * @return array
     */
    public function getUsageByUsers($id)
    {
        $query = /** @lang SQL */
            'SELECT U.id, login, `name`, ref
              FROM (
               SELECT
                 id,
                 "User" AS ref
               FROM User U
               WHERE U.userGroupId = ?
               UNION ALL
               SELECT
                 userId AS id,
                 "UserGroup" AS ref
               FROM
                 UserToUserGroup UUG
               WHERE userGroupId = ?) Users
          INNER JOIN User U ON U.id = Users.id';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParams([(int)$id, (int)$id]);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name, description FROM UserGroup WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setMapClassName(UserGroupData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     * @return UserGroupData
     */
    public function getByName($name)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name, description FROM UserGroup WHERE name = ? LIMIT 1');
        $queryData->addParam($name);
        $queryData->setMapClassName(UserGroupData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name, description FROM UserGroup ORDER BY name');
        $queryData->setMapClassName(UserGroupData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
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
            'SELECT id, name, description FROM UserGroup WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(UserGroupData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserGroup WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(UserGroupData::class);
        $queryData->setSelect('id, name, description');
        $queryData->setFrom('UserGroup');
        $queryData->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ? OR description LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($SearchData->getLimitStart());
        $queryData->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        $queryRes['count'] = $queryData->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Creates an item
     *
     * @param UserGroupData $itemData
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(__u('Nombre de grupo duplicado'), SPException::INFO);
        }

        $query = /** @lang SQL */
            'INSERT INTO UserGroup SET name = ?, description = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getDescription());
        $queryData->setOnErrorMessage(__u('Error al crear el grupo'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserGroupData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT name FROM UserGroup WHERE UPPER(name) = UPPER(?)');
        $queryData->addParam($itemData->getName());

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param UserGroupData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(__u('Nombre de grupo duplicado'), SPException::INFO);
        }

        $queryData = new QueryData();
        $queryData->setQuery('UPDATE UserGroup SET name = ?, description = ? WHERE id = ? LIMIT 1');
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getDescription());
        $queryData->addParam($itemData->getId());
        $queryData->setOnErrorMessage(__u('Error al actualizar el grupo'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserGroupData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT name FROM UserGroup WHERE UPPER(name) = UPPER(?) AND id <> ?');
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getId());

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }
}