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

namespace SP\Domain\Notification\Ports;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class NotificationService
 *
 * @template T of NotificationModel
 */
interface NotificationService
{
    /**
     * Creates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(NotificationModel $notification): int;

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(NotificationModel $notification): int;

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param int[] $ids
     *
     * @return array<T>
     */
    public function getByIdBatch(array $ids): array;

    /**
     * Deletes an item preserving the sticky ones
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): NotificationService;

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function deleteAdmin(int $id): NotificationService;

    /**
     * Deletes an item
     *
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteAdminBatch(array $ids): int;

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return NotificationModel
     * @throws NoSuchItemException
     */
    public function getById(int $id): NotificationModel;

    /**
     * Returns all the items
     *
     * @return array<T>
     */
    public function getAll(): array;

    /**
     * Marcar una notificación como leída
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function setCheckedById(int $id): void;

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @param string $component
     * @param int $id
     * @return array<T>
     */
    public function getForUserIdByDate(string $component, int $id): array;

    /**
     * @param int $id
     * @return array<T>
     */
    public function getAllForUserId(int $id): array;

    /**
     * @return array<T>
     */
    public function getAllActiveForCurrentUser(): array;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @return QueryResult<T>
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @param int $userId
     * @return QueryResult<T>
     */
    public function searchForUserId(ItemSearchDto $itemSearchData, int $userId): QueryResult;
}
