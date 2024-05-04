<?php
declare(strict_types=1);
/**
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

namespace SP\Infrastructure\Notification\Repositories;

use Aura\SqlQuery\Common\SelectInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Domain\Notification\Ports\NotificationRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class Notification
 *
 * @template T of NotificationModel
 */
final class Notification extends BaseRepository implements NotificationRepository
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param NotificationModel $notification
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(NotificationModel $notification): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(NotificationModel::TABLE)
            ->cols($notification->toArray(null, ['id', 'date']))
            ->set('date', 'UNIX_TIMESTAMP()');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while adding the notification'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param NotificationModel $notification
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(NotificationModel $notification): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(NotificationModel::TABLE)
            ->cols($notification->toArray(null, ['id']))
            ->set('date', 'UNIX_TIMESTAMP()')
            ->where('id = :id', ['id' => $notification->getId()])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the notification'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(NotificationModel::TABLE)
            ->where('id = :id AND sticky = 0', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the notification'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteAdmin(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(NotificationModel::TABLE)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the notification'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Deletes an item
     *
     * @param array $notificationsId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteAdminBatch(array $notificationsId): QueryResult
    {
        if (count($notificationsId) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(NotificationModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $notificationsId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the notifications'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $notificationId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $notificationId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $notificationId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, NotificationModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->orderBy(['id']);

        return $this->db->runQuery(QueryData::buildWithMapper($query, NotificationModel::class));
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $notificationsId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $notificationsId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->where('id IN (:ids)', ['ids' => $notificationsId]);

        $queryData = QueryData::buildWithMapper($query, NotificationModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $notificationsId
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $notificationsId): QueryResult
    {
        if (count($notificationsId) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(NotificationModel::TABLE)
            ->where('id IN (:ids) AND sticky = 0', ['ids' => $notificationsId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the notifications'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @param int $userId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForUserId(ItemSearchDto $itemSearchData, int $userId): QueryResult
    {
        $query = $this->getBaseSearch($itemSearchData)
                      ->where(
                          '(userId = :userId OR (userId IS NULL AND onlyAdmin = 0) OR sticky = 1)',
                          ['userId' => $userId]
                      );

        $queryData = QueryData::build($query)->setMapClassName(NotificationModel::class);

        return $this->db->runQuery($queryData, true);
    }

    private function getBaseSearch(ItemSearchDto $itemSearchData): SelectInterface
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->orderBy(['date DESC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('(type LIKE :type OR component LIKE :component)');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['type' => $search, 'component' => $search]);
        }

        return $query;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @param int $userId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForAdmin(ItemSearchDto $itemSearchData, int $userId): QueryResult
    {
        $query = $this->getBaseSearch($itemSearchData)
                      ->where('(userId = :userId OR onlyAdmin = 1 OR sticky = 1)', ['userId' => $userId]);

        $queryData = QueryData::build($query)->setMapClassName(NotificationModel::class);

        return $this->db->runQuery($queryData, true);
    }

    /**
     * Marcar una notificación como leída
     *
     * @param int $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function setCheckedById(int $id): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(NotificationModel::TABLE)
            ->cols(['checked' => 1])
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the notification'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @param string $component
     * @param int $userId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUserIdByDate(string $component, int $userId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->where('userId = :userId AND component = :component AND (UNIX_TIMESTAMP() - date) <= 86400')
            ->bindValues([
                             'userId' => $userId,
                             'component' => $component
                         ])
            ->orderBy(['id']);

        $queryData = QueryData::buildWithMapper($query, NotificationModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * @param int $userId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllForUserId(int $userId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->where('(userId = :userId OR (userId IS NULL AND sticky = 1)) AND onlyAdmin = 0')
            ->bindValues(['userId' => $userId])
            ->orderBy(['date DESC']);

        $queryData = QueryData::buildWithMapper($query, NotificationModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * @param int $userId
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllActiveForUserId(int $userId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->where('(userId = :userId OR sticky = 1) AND onlyAdmin = 0 AND checked = 0')
            ->bindValues(['userId' => $userId])
            ->orderBy(['date DESC']);

        $queryData = QueryData::buildWithMapper($query, NotificationModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * @param int $userId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllActiveForAdmin(int $userId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(NotificationModel::TABLE)
            ->cols(NotificationModel::getCols())
            ->where('(userId = :userId OR sticky = 1 OR userId IS NULL) AND checked = 0')
            ->bindValues(['userId' => $userId])
            ->orderBy(['date DESC']);

        $queryData = QueryData::buildWithMapper($query, NotificationModel::class);

        return $this->db->runQuery($queryData);
    }
}
