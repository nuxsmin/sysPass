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

namespace SP\Repositories\ItemPreset;

use SP\DataModel\ItemPresetData;
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
class ItemPresetRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param ItemPresetData $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery(
            'INSERT INTO ItemPreset 
                SET type = ?,
                userId = ?, 
                userGroupId = ?, 
                userProfileId = ?, 
                `fixed` = ?, 
                priority = ?,
                `data` = ?,
                `hash` = ?');
        $queryData->setParams([
            $itemData->getType(),
            $itemData->getUserId(),
            $itemData->getUserGroupId(),
            $itemData->getUserProfileId(),
            $itemData->getFixed(),
            $itemData->getPriority(),
            $itemData->getData(),
            $itemData->getHash()
        ]);
        $queryData->setOnErrorMessage(__u('Error al crear permiso'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param ItemPresetData $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery(
            'UPDATE ItemPreset 
                SET type = ?, 
                userId = ?, 
                userGroupId = ?, 
                userProfileId = ?, 
                `fixed` = ?, 
                priority = ?,
                `data` = ?,
                `hash` = ?
                WHERE id = ? LIMIT 1');
        $queryData->setParams([
            $itemData->getType(),
            $itemData->getUserId(),
            $itemData->getUserGroupId(),
            $itemData->getUserProfileId(),
            $itemData->getFixed(),
            $itemData->getPriority(),
            $itemData->getData(),
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
        $queryData->setQuery('DELETE FROM ItemPreset WHERE id = ? LIMIT 1');
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
        $queryData->setMapClassName(ItemPresetData::class);
        $queryData->setQuery(
            'SELECT id, type, userId, userGroupId, userProfileId, `fixed`, priority, `data` 
        FROM ItemPreset WHERE id = ? LIMIT 1');
        $queryData->setParams([$id]);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param string $type
     * @param int    $userId
     * @param int    $userGroupId
     * @param int    $userProfileId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByFilter(string $type, int $userId, int $userGroupId, int $userProfileId)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(ItemPresetData::class);
        $queryData->setQuery(
            'SELECT id, type, userId, userGroupId, userProfileId, `fixed`, priority, `data` 
        FROM ItemPreset 
        WHERE type = ? AND (userId = ? OR userGroupId = ? OR userProfileId = ?)
        ORDER BY priority DESC, userId DESC, userProfileId DESC, userGroupId DESC
        LIMIT 1');

        $queryData->setParams([$type, $userId, $userGroupId, $userProfileId]);

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
        $queryData->setMapClassName(ItemPresetData::class);
        $queryData->setQuery(
            'SELECT id, type, userId, userGroupId, userProfileId, `fixed`, priority, `data` 
        FROM ItemPreset');

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
        $queryData->setMapClassName(ItemPresetData::class);
        $queryData->setQuery(
            'SELECT type, userId, userGroupId, userProfileId, `fixed`, priority, `data`
            FROM ItemPreset WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
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
        $queryData->setQuery('DELETE FROM ItemPreset WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
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
            'IP.id,
            IP.type,
            IP.userId,
            IP.userGroupId,
            IP.userProfileId,
            IP.`fixed`,
            IP.priority,
            IP.data,
            U.name AS userName,
            UP.name AS userProfileName,
            UG.name AS userGroupName');
        $queryData->setFrom('
            ItemPreset IP 
            LEFT JOIN User U ON IP.userId = U.id 
            LEFT JOIN UserProfile UP ON IP.userProfileId = UP.id 
            LEFT JOIN UserGroup UG ON IP.userGroupId = UG.id');
        $queryData->setOrder('IP.type, IP.priority DESC, IP.userId DESC, IP.userProfileId DESC, IP.userGroupId DESC');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('IP.type LIKE ? OR U.name LIKE ? OR UP.name LIKE ? OR UG.name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($itemSearchData->getLimitStart());
        $queryData->addParam($itemSearchData->getLimitCount());

        return $this->db->doSelect($queryData, true);
    }
}