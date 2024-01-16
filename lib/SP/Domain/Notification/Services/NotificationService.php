<?php
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
use SP\DataModel\ItemSearchData;
use SP\DataModel\NotificationData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Notification\Ports\NotificationRepository;
use SP\Domain\Notification\Ports\NotificationServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Notification\Repositories\NotificationBaseRepository;

/**
 * Class NotificationService
 *
 * @package SP\Domain\Notification\Services
 */
final class NotificationService extends Service implements NotificationServiceInterface
{
    protected NotificationBaseRepository $notificationRepository;

    public function __construct(Application $application, NotificationRepository $notificationRepository)
    {
        parent::__construct($application);

        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Creates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(NotificationData $itemData): int
    {
        return $this->notificationRepository->create($itemData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(NotificationData $itemData): int
    {
        return $this->notificationRepository->update($itemData);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param  int[]  $ids
     *
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): array
    {
        return $this->notificationRepository->getByIdBatch($ids)->getDataAsArray();
    }

    /**
     * Deletes an item preserving the sticky ones
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): NotificationServiceInterface
    {
        if ($this->notificationRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Notification not found'), SPException::INFO);
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
    public function deleteAdmin(int $id): NotificationServiceInterface
    {
        if ($this->notificationRepository->deleteAdmin($id) === 0) {
            throw new NoSuchItemException(__u('Notification not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * Deletes an item
     *
     * @param  int[]  $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteAdminBatch(array $ids): int
    {
        $count = $this->notificationRepository->deleteAdminBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while deleting the notifications'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->notificationRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while deleting the notifications'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * Returns the item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): NotificationData
    {
        $result = $this->notificationRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Notification not found'), SPException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns all the items
     *
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
    {
        return $this->notificationRepository->getAll()->getDataAsArray();
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
            throw new NoSuchItemException(__u('Notification not found'), SPException::INFO);
        }
    }

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUserIdByDate(string $component, int $id): array
    {
        return $this->notificationRepository->getForUserIdByDate($component, $id)->getDataAsArray();
    }

    /**
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllForUserId(int $id): array
    {
        return $this->notificationRepository->getAllForUserId($id)->getDataAsArray();
    }

    /**
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllActiveForUserId(int $id): array
    {
        if ($this->context->getUserData()->getIsAdminApp()) {
            return $this->notificationRepository->getAllActiveForAdmin($id)->getDataAsArray();
        }

        return $this->notificationRepository->getAllActiveForUserId($id)->getDataAsArray();
    }

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $userData = $this->context->getUserData();

        if ($userData->getIsAdminApp()) {
            return $this->notificationRepository->searchForAdmin($itemSearchData, $userData->getId());
        }

        return $this->notificationRepository->searchForUserId($itemSearchData, $userData->getId());
    }

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForUserId(ItemSearchData $itemSearchData, int $userId): QueryResult
    {
        return $this->notificationRepository->searchForUserId($itemSearchData, $userId);
    }
}
