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

namespace SP\Services\Notification;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\NotificationData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Notification\NotificationRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;

/**
 * Class NotificationService
 *
 * @package SP\Services\Notification
 */
final class NotificationService extends Service
{
    /**
     * @var NotificationRepository
     */
    protected $notificationRepository;

    /**
     * Creates an item
     *
     * @param NotificationData $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(NotificationData $itemData)
    {
        return $this->notificationRepository->create($itemData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param NotificationData $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(NotificationData $itemData)
    {
        return $this->notificationRepository->update($itemData);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     *
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids)
    {
        return $this->notificationRepository->getByIdBatch($ids)->getDataAsArray();
    }

    /**
     * Deletes an item preserving the sticky ones
     *
     * @param $id
     *
     * @return NotificationService
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        if ($this->notificationRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Notification not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return NotificationService
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function deleteAdmin($id)
    {
        if ($this->notificationRepository->deleteAdmin($id) === 0) {
            throw new NoSuchItemException(__u('Notification not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * Deletes an item
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteAdminBatch(array $ids)
    {
        if (($count = $this->notificationRepository->deleteAdminBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the notifications'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->notificationRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the notifications'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return NotificationData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
    {
        $result = $this->notificationRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Notification not found'), NoSuchItemException::INFO);
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
    public function getAll()
    {
        return $this->notificationRepository->getAll()->getDataAsArray();
    }

    /**
     * Marcar una notificación como leída
     *
     * @param $id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function setCheckedById($id)
    {
        if ($this->notificationRepository->setCheckedById($id) === 0) {
            throw new NoSuchItemException(__u('Notification not found'), NoSuchItemException::INFO);
        }
    }

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @param $component
     * @param $id
     *
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUserIdByDate($component, $id)
    {
        return $this->notificationRepository->getForUserIdByDate($component, $id)->getDataAsArray();
    }

    /**
     * @param $id
     *
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllForUserId($id)
    {
        return $this->notificationRepository->getAllForUserId($id)->getDataAsArray();
    }

    /**
     * @param $id
     *
     * @return NotificationData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllActiveForUserId($id)
    {
        if ($this->context->getUserData()->getIsAdminApp()) {
            return $this->notificationRepository->getAllActiveForAdmin($id)->getDataAsArray();
        }

        return $this->notificationRepository->getAllActiveForUserId($id)->getDataAsArray();
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
        $userData = $this->context->getUserData();

        if ($userData->getIsAdminApp()) {
            return $this->notificationRepository->searchForAdmin($itemSearchData, $userData->getId());
        }

        return $this->notificationRepository->searchForUserId($itemSearchData, $userData->getId());
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     * @param int            $userId
     *
     * @return mixed
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForUserId(ItemSearchData $itemSearchData, $userId)
    {
        return $this->notificationRepository->searchForUserId($itemSearchData, $userId);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->notificationRepository = $this->dic->get(NotificationRepository::class);
    }
}