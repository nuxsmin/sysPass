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

namespace SP\Services\ItemPreset;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemPresetData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\ItemPreset\ItemPresetRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;

/**
 * Class ItemPresetService
 *
 * @package SP\Services\Account
 */
final class ItemPresetService extends Service
{
    /**
     * @var ItemPresetRepository
     */
    private $itemPresetRepository;

    /**
     * @param ItemPresetRequest $itemPresetRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ItemPresetRequest $itemPresetRequest)
    {
        return $this->itemPresetRepository->create($itemPresetRequest->prepareToPersist());
    }

    /**
     * @param ItemPresetRequest $itemPresetRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ItemPresetRequest $itemPresetRequest)
    {
        return $this->itemPresetRepository->update($itemPresetRequest->prepareToPersist());
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return ItemPresetService
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        if ($this->itemPresetRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Value not found'));
        }

        return $this;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return ItemPresetData
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        $result = $this->itemPresetRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Value not found'));
        }

        return $result->getData();
    }

    /**
     * Returns all the items
     *
     * @return ItemPresetData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll()
    {
        return $this->itemPresetRepository->getAll()->getDataAsArray();
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
        return $this->itemPresetRepository->search($itemSearchData);
    }

    /**
     * @param string $type
     *
     * @return ItemPresetData
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForCurrentUser(string $type)
    {
        $userData = $this->context->getUserData();

        return $this->getForUser($type, $userData->getId(), $userData->getUserGroupId(), $userData->getUserProfileId());
    }

    /**
     * @param string $type
     * @param int    $userId
     * @param int    $userGroupId
     * @param int    $userProfileId
     *
     * @return ItemPresetData
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUser(string $type, int $userId, int $userGroupId, int $userProfileId)
    {
        $result = $this->itemPresetRepository->getByFilter(
            $type,
            $userId,
            $userGroupId,
            $userProfileId
        );

        if ($result->getNumRows() === 1) {
            return $result->getData();
        }

        return null;
    }

    /**
     * @param array $ids
     *
     * @return int
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->itemPresetRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the values'), ServiceException::WARNING);
        }

        return $count;
    }

    protected function initialize()
    {
        $this->itemPresetRepository = $this->dic->get(ItemPresetRepository::class);
    }
}