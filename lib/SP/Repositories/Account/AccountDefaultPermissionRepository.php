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

namespace SP\Repositories\Account;

use SP\DataModel\AccountDefaultPermissionData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountDefaultPermissionRepository
 *
 * @package SP\Repositories\Account
 */
class AccountDefaultPermissionRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param AccountDefaultPermissionData $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery(
            'INSERT INTO AccountDefaultPermission 
                SET userId = ?, 
                userGroupId = ?, 
                userProfileId = ?, 
                `fixed` = ?, 
                priority = ?,
                permission = ?,
                `hash` = ?');
        $queryData->setParams([
            $itemData->getUserId(),
            $itemData->getUserGroupId(),
            $itemData->getUserProfileId(),
            $itemData->getFixed(),
            $itemData->getPriority(),
            $itemData->getPermission(),
            $itemData->getHash()
        ]);
        $queryData->setOnErrorMessage(__u('Error al crear permiso'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param AccountDefaultPermissionData $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery(
            'UPDATE AccountDefaultPermission 
                SET userId = ?, 
                userGroupId = ?, 
                userProfileId = ?, 
                `fixed` = ?, 
                priority = ?,
                permission = ?,
                `hash` = ?
                WHERE id = ? LIMIT 1');
        $queryData->setParams([
            $itemData->getUserId(),
            $itemData->getUserGroupId(),
            $itemData->getUserProfileId(),
            $itemData->getFixed(),
            $itemData->getPriority(),
            $itemData->getPermission(),
            $itemData->getHash(),
            $itemData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error al actualizar permiso'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountDefaultPermission WHERE id = ? LIMIT 1');
        $queryData->setParams([$id]);
        $queryData->setOnErrorMessage(__u('Error al eliminar permiso'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(AccountDefaultPermissionData::class);
        $queryData->setQuery(
            'SELECT id, userId, userGroupId, userProfileId, `fixed`, priority, permission 
        FROM AccountDefaultPermission WHERE id = ? LIMIT 1');
        $queryData->setParams([$id]);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $userId
     * @param int $userGroupId
     * @param int $userProfileId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByFilter(int $userId, int $userGroupId, int $userProfileId)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(AccountDefaultPermissionData::class);
        $queryData->setQuery(
            'SELECT id, userId, userGroupId, userProfileId, `fixed`, priority, permission 
        FROM AccountDefaultPermission 
        WHERE userId = ? OR userGroupId = ? OR userProfileId = ?
        ORDER BY priority DESC, userId DESC, userProfileId DESC, userGroupId DESC
        LIMIT 1');

        $queryData->setParams([$userId, $userGroupId, $userProfileId]);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(AccountDefaultPermissionData::class);
        $queryData->setQuery(
            'SELECT id, userId, userGroupId, userProfileId, `fixed`, priority, permission 
        FROM AccountDefaultPermission');

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return new QueryResult();
        }

        $queryData = new QueryData();
        $queryData->setMapClassName(AccountDefaultPermissionData::class);
        $queryData->setQuery(
            'SELECT userId, userGroupId, userProfileId, `fixed`, priority, permission
            FROM AccountDefaultPermission WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountDefaultPermission WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar los permisos'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setSelect(
            'ADP.id,
            ADP.userId,
            ADP.userGroupId,
            ADP.userProfileId,
            ADP.`fixed`,
            ADP.priority,
            ADP.permission,
            U.name AS userName,
            UP.name AS userProfileName,
            UG.name AS userGroupName');
        $queryData->setFrom('
            AccountDefaultPermission ADP 
            LEFT JOIN User U ON ADP.userId = U.id 
            LEFT JOIN UserProfile UP ON ADP.userProfileId = UP.id 
            LEFT JOIN UserGroup UG ON ADP.userGroupId = UG.id');
        $queryData->setOrder('id');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('U.name LIKE ? OR UP.name LIKE ? OR UG.name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($itemSearchData->getLimitStart());
        $queryData->addParam($itemSearchData->getLimitCount());

        return $this->db->doSelect($queryData, true);
    }

    /**
     * @param AccountDefaultPermissionData $data
     *
     * @return string
     */
    private function getHash(AccountDefaultPermissionData $data)
    {
        return sha1((int)$data->getUserId() . (int)$data->getUserGroupId() . (int)$data->getUserProfileId() . (int)$data->getPriority());
    }
}