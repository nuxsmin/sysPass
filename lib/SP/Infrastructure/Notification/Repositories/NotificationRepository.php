<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Infrastructure\Notification\Repositories;

use RuntimeException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\NotificationData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Notification\Ports\NotificationRepositoryInterface;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Mvc\Model\QueryCondition;

/**
 * Class NotificationRepository
 *
 * @package SP\Infrastructure\Notification\Repositories
 */
final class NotificationRepository extends Repository implements NotificationRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param  NotificationData  $itemData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData): QueryResult
    {
        $query = /** @lang SQL */
            'INSERT INTO Notification 
            SET type = ?,
            component = ?,
            description = ?,
            `date` = UNIX_TIMESTAMP(),
            checked = 0,
            userId = ?,
            sticky = ?,
            onlyAdmin = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getType(),
            $itemData->getComponent(),
            $itemData->getDescription(),
            $itemData->getUserId() ?: null,
            $itemData->isSticky(),
            $itemData->isOnlyAdmin(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while adding the notification'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param  NotificationData  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData): int
    {
        $query = /** @lang SQL */
            'UPDATE Notification 
            SET type = ?,
            component = ?,
            description = ?,
            `date` = UNIX_TIMESTAMP(),
            checked = ?,
            userId = ?,
            sticky = ?,
            onlyAdmin = ? 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getType(),
            $itemData->getComponent(),
            $itemData->getDescription(),
            $itemData->isChecked(),
            $itemData->getUserId() ?: null,
            $itemData->isSticky(),
            $itemData->isOnlyAdmin(),
            $itemData->getId(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the notification'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Notification WHERE id = ? AND sticky = 0 LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the notification'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteAdmin(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Notification WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the notification'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteAdminBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Notification WHERE id IN ('.$this->buildParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the notifications'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id, 
            type,
            component,
            description,
            `date`,
            userId,
            checked,
            sticky,
            onlyAdmin 
            FROM Notification 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while retrieving notification'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            type,
            component,
            description,
            `date`,
            userId,
            checked,
            sticky,
            onlyAdmin 
            FROM Notification
            ORDER BY id';

        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setQuery($query);
        $queryData->setOnErrorMessage(__u('Error while retrieving the notifications'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
        }

        $query = /** @lang SQL */
            'SELECT id, 
            type,
            component,
            description,
            `date`,
            userId,
            checked,
            sticky,
            onlyAdmin 
            FROM Notification 
            WHERE id IN ('.$this->buildParamsFromArray($ids).')
            ORDER BY id';

        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery(
            'DELETE FROM Notification WHERE id IN ('.$this->buildParamsFromArray($ids).') AND sticky = 0'
        );
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the notifications'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse(int $id): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  mixed  $itemData
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  mixed  $itemData
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setSelect('id, type, component, description, `date`, checked, userId, sticky, onlyAdmin');
        $queryData->setFrom('Notification');
        $queryData->setOrder('`date` DESC');

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere('type LIKE ? OR component LIKE ? OR description LIKE ?');

            $search = '%'.$itemSearchData->getSeachString().'%';
            $queryData->addParam($search);
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
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForUserId(
        ItemSearchData $itemSearchData,
        int $userId
    ): QueryResult {
        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setSelect('id, type, component, description, `date`, checked, userId, sticky, onlyAdmin');
        $queryData->setFrom('Notification');
        $queryData->setOrder('`date` DESC');

        $queryCondition = new QueryCondition();
        $queryCondition
            ->addFilter('userId = ?', [$userId])
            ->addFilter('(userId IS NULL AND onlyAdmin = 0)')
            ->addFilter('sticky = 1');

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere(
                '(type LIKE ? OR component LIKE ? OR description LIKE ?) AND '
                .$queryCondition->getFilters(QueryCondition::CONDITION_OR)
            );

            $search = '%'.$itemSearchData->getSeachString().'%';
            $queryData->setParams(array_merge([$search, $search, $search], $queryCondition->getParams()));
        } else {
            $queryData->setWhere($queryCondition->getFilters(QueryCondition::CONDITION_OR));
            $queryData->setParams($queryCondition->getParams());
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForAdmin(
        ItemSearchData $itemSearchData,
        int $userId
    ): QueryResult {
        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setSelect('id, type, component, description, `date`, checked, userId, sticky, onlyAdmin');
        $queryData->setFrom('Notification');
        $queryData->setOrder('`date` DESC');

        $queryCondition = new QueryCondition();
        $queryCondition
            ->addFilter('userId = ?', [$userId])
            ->addFilter('onlyAdmin = 1')
            ->addFilter('sticky = 1');

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere(
                '(type LIKE ? OR component LIKE ? OR description LIKE ?) AND '
                .$queryCondition->getFilters(QueryCondition::CONDITION_OR)
            );

            $search = '%'.$itemSearchData->getSeachString().'%';

            $queryData->setParams(array_merge([$search, $search, $search], $queryCondition->getParams()));
        } else {
            $queryData->setWhere($queryCondition->getFilters(QueryCondition::CONDITION_OR));
            $queryData->setParams($queryCondition->getParams());
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Marcar una notificación como leída
     *
     * @param  int  $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function setCheckedById(int $id): int
    {
        $query = /** @lang SQL */
            'UPDATE Notification SET checked = 1 WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while updating the notification'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @param  string  $component
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUserIdByDate(string $component, int $userId): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            type,
            component,
            description,
            `date`,
            userId,
            checked,
            sticky,
            onlyAdmin 
            FROM Notification 
            WHERE component = ?
            AND (UNIX_TIMESTAMP() - `date`) <= 86400
            AND userId = ?
            ORDER BY id';

        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setQuery($query);
        $queryData->setParams([$component, $userId]);
        $queryData->setOnErrorMessage(__u('Error while retrieving the notifications'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllForUserId(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            type,
            component,
            description,
            `date`,
            userId,
            checked,
            sticky,
            onlyAdmin 
            FROM Notification 
            WHERE (userId = ? OR (userId IS NULL AND sticky = 1))
            AND onlyAdmin = 0 
            ORDER BY `date` DESC ';

        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while retrieving the notifications'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllActiveForUserId(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            type,
            component,
            description,
            `date`,
            userId,
            checked,
            sticky,
            onlyAdmin 
            FROM Notification 
            WHERE (userId = ? OR sticky = 1) 
            AND onlyAdmin = 0 
            AND checked = 0
            ORDER BY `date` DESC ';

        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while retrieving the notifications'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllActiveForAdmin(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            type,
            component,
            description,
            `date`,
            userId,
            checked,
            sticky,
            onlyAdmin 
            FROM Notification 
            WHERE (userId = ? OR sticky = 1 OR userId IS NULL) 
            AND checked = 0
            ORDER BY `date` DESC ';

        $queryData = new QueryData();
        $queryData->setMapClassName(NotificationData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while retrieving the notifications'));

        return $this->db->doSelect($queryData);
    }
}
