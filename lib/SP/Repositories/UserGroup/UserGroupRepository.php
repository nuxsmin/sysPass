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

namespace SP\Repositories\UserGroup;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserGroupData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class UserGroupRepository
 *
 * @package SP\Repositories\UserGroup
 */
final class UserGroupRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserGroup WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the group'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
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

        return $this->db->doSelect($queryData)->getNumRows() > 0;
    }

    /**
     * Returns the items that are using the given group id
     *
     * @param $id int
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
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

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the users that are using the given group id
     *
     * @param $id int
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
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

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(UserGroupData::class);
        $queryData->setQuery('SELECT id, `name`, description FROM UserGroup WHERE id = ? LIMIT 1');
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName($name)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(UserGroupData::class);
        $queryData->setQuery('SELECT id, `name`, description FROM UserGroup WHERE name = ? LIMIT 1');
        $queryData->addParam($name);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(UserGroupData::class);
        $queryData->setQuery('SELECT id, `name`, description FROM UserGroup ORDER BY name');

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return UserGroupData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT id, name, description FROM UserGroup WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(UserGroupData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData)->getDataAsArray();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserGroup WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
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
        $queryData->setMapClassName(UserGroupData::class);
        $queryData->setSelect('id, name, description');
        $queryData->setFrom('UserGroup');
        $queryData->setOrder('name');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ? OR description LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Creates an item
     *
     * @param UserGroupData $itemData
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated group name'));
        }

        $query = /** @lang SQL */
            'INSERT INTO UserGroup SET `name` = ?, description = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$itemData->getName(), $itemData->getDescription()]);
        $queryData->setOnErrorMessage(__u('Error while creating the group'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserGroupData $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT `name` FROM UserGroup WHERE UPPER(`name`) = UPPER(?)');
        $queryData->addParam($itemData->getName());

        return $this->db->doSelect($queryData)->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param UserGroupData $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated group name'));
        }

        $queryData = new QueryData();
        $queryData->setQuery('UPDATE UserGroup SET `name` = ?, description = ? WHERE id = ? LIMIT 1');
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getDescription(),
            $itemData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the group'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserGroupData $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT `name` FROM UserGroup WHERE UPPER(`name`) = UPPER(?) AND id <> ?');
        $queryData->setParams([$itemData->getName(), $itemData->getId()]);

        return $this->db->doSelect($queryData)->getNumRows() > 0;
    }
}