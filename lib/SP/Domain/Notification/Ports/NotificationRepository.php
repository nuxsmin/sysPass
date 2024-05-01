<?php
declare(strict_types=1);
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

namespace SP\Domain\Notification\Ports;

use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class NotificationRepository
 *
 * @template T of NotificationModel
 */
interface NotificationRepository extends Repository
{
    /**
     * Creates an item
     *
     * @param NotificationModel $notification
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(NotificationModel $notification): QueryResult;

    /**
     * Updates an item
     *
     * @param NotificationModel $notification
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(NotificationModel $notification): int;

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult;

    /**
     * Returns the item for given id
     *
     * @param int $notificationId
     *
     * @return QueryResult<T>
     */
    public function getById(int $notificationId): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult;

    /**
     * Returns all the items for given ids
     *
     * @param array $notificationsId
     *
     * @return QueryResult<T>
     */
    public function getByIdBatch(array $notificationsId): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array $notificationsId
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $notificationsId): QueryResult;

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteAdmin(int $id): QueryResult;

    /**
     * Deletes an item
     *
     * @param array $notificationsId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteAdminBatch(array $notificationsId): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @param int $userId
     *
     * @return QueryResult<T>
     */
    public function searchForUserId(ItemSearchDto $itemSearchData, int $userId): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @param int $userId
     *
     * @return QueryResult<T>
     */
    public function searchForAdmin(ItemSearchDto $itemSearchData, int $userId): QueryResult;

    /**
     * Marcar una notificación como leída
     *
     * @param int $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function setCheckedById(int $id): int;

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @param string $component
     * @param int $userId
     *
     * @return QueryResult<T>
     */
    public function getForUserIdByDate(string $component, int $userId): QueryResult;

    /**
     * @param int $userId
     *
     * @return QueryResult<T>
     */
    public function getAllForUserId(int $userId): QueryResult;

    /**
     * @param int $userId
     * @return QueryResult<T>
     */
    public function getAllActiveForUserId(int $userId): QueryResult;

    /**
     * @param int $userId
     *
     * @return QueryResult<T>
     */
    public function getAllActiveForAdmin(int $userId): QueryResult;
}
