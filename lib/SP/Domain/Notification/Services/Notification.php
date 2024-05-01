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

namespace SP\Domain\Notification\Services;

use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Domain\Notification\Ports\NotificationRepository;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class NotificationService
 *
 * @template T of NotificationModel
 */
final class Notification extends Service implements NotificationService
{

    public function __construct(
        Application                             $application,
        private readonly NotificationRepository $notificationRepository
    ) {
        parent::__construct($application);
    }

    /**
     * Creates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(NotificationModel $notification): int
    {
        return $this->notificationRepository->create($notification)->getLastId();
    }

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(NotificationModel $notification): int
    {
        return $this->notificationRepository->update($notification);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param int[] $ids
     *
     * @return array<T>
     */
    public function getByIdBatch(array $ids): array
    {
        return $this->notificationRepository
            ->getByIdBatch($ids)
            ->getDataAsArray(NotificationModel::class);
    }

    /**
     * Deletes an item preserving the sticky ones
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): NotificationService
    {
        if ($this->notificationRepository->delete($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Notification not found'));
        }

        return $this;
    }

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function deleteAdmin(int $id): NotificationService
    {
        if ($this->notificationRepository->deleteAdmin($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Notification not found'));
        }

        return $this;
    }

    /**
     * Deletes an item
     *
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteAdminBatch(array $ids): int
    {
        $count = $this->notificationRepository->deleteAdminBatch($ids)->getAffectedNumRows();

        if ($count !== count($ids)) {
            throw ServiceException::warning(__u('Error while deleting the notifications'));
        }

        return $count;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->notificationRepository->deleteByIdBatch($ids)->getAffectedNumRows();

        if ($count !== count($ids)) {
            throw ServiceException::warning(__u('Error while deleting the notifications'));
        }

        return $count;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return NotificationModel
     * @throws NoSuchItemException
     */
    public function getById(int $id): NotificationModel
    {
        $result = $this->notificationRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Notification not found'));
        }

        return $result->getData(NotificationModel::class);
    }

    /**
     * Returns all the items
     *
     * @return array<T>
     */
    public function getAll(): array
    {
        return $this->notificationRepository
            ->getAll()
            ->getDataAsArray(NotificationModel::class);
    }

    /**
     * Marcar una notificación como leída
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function setCheckedById(int $id): void
    {
        if ($this->notificationRepository->setCheckedById($id) === 0) {
            throw NoSuchItemException::info(__u('Notification not found'));
        }
    }

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @param string $component
     * @param int $id
     * @return array<T>
     */
    public function getForUserIdByDate(string $component, int $id): array
    {
        return $this->notificationRepository
            ->getForUserIdByDate($component, $id)
            ->getDataAsArray(NotificationModel::class);
    }

    /**
     * @param int $id
     * @return array<T>
     */
    public function getAllForUserId(int $id): array
    {
        return $this->notificationRepository
            ->getAllForUserId($id)
            ->getDataAsArray(NotificationModel::class);
    }

    /**
     * @return array<T>
     */
    public function getAllActiveForCurrentUser(): array
    {
        $userData = $this->context->getUserData();

        if ($userData->getIsAdminApp()) {
            return $this->notificationRepository
                ->getAllActiveForAdmin($userData->getId())
                ->getDataAsArray(NotificationModel::class);
        }

        return $this->notificationRepository
            ->getAllActiveForUserId($userData->getId())
            ->getDataAsArray(NotificationModel::class);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @return QueryResult<T>
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $userData = $this->context->getUserData();

        if ($userData->getIsAdminApp()) {
            return $this->notificationRepository
                ->searchForAdmin($itemSearchData, $userData->getId());
        }

        return $this->notificationRepository
            ->searchForUserId($itemSearchData, $userData->getId());
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     * @param int $userId
     * @return QueryResult<T>
     */
    public function searchForUserId(ItemSearchDto $itemSearchData, int $userId): QueryResult
    {
        return $this->notificationRepository->searchForUserId($itemSearchData, $userId);
    }
}
