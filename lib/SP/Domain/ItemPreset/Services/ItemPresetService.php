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

namespace SP\Domain\ItemPreset\Services;

use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemPresetData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\ItemPreset\In\ItemPresetRepositoryInterface;
use SP\Domain\ItemPreset\ItemPresetServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\ItemPreset\Repositories\ItemPresetRepository;

/**
 * Class ItemPresetService
 *
 * @package SP\Domain\Account\Services
 */
final class ItemPresetService extends Service implements ItemPresetServiceInterface
{
    private ItemPresetRepository $itemPresetRepository;

    public function __construct(Application $application, ItemPresetRepositoryInterface $itemPresetRepository)
    {
        parent::__construct($application);

        $this->itemPresetRepository = $itemPresetRepository;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ItemPresetRequest $itemPresetRequest): int
    {
        return $this->itemPresetRepository->create($itemPresetRequest->prepareToPersist());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ItemPresetRequest $itemPresetRequest): int
    {
        return $this->itemPresetRepository->update($itemPresetRequest->prepareToPersist());
    }

    /**
     * Deletes an item
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): ItemPresetService
    {
        if ($this->itemPresetRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Value not found'));
        }

        return $this;
    }

    /**
     * Returns the item for given id
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): ItemPresetData
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
    public function getAll(): array
    {
        return $this->itemPresetRepository->getAll()->getDataAsArray();
    }

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->itemPresetRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForCurrentUser(string $type): ?ItemPresetData
    {
        $userData = $this->context->getUserData();

        return $this->getForUser(
            $type,
            $userData->getId(),
            $userData->getUserGroupId(),
            $userData->getUserProfileId()
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUser(
        string $type,
        int $userId,
        int $userGroupId,
        int $userProfileId
    ): ?ItemPresetData {
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
     * @param  int[]  $ids
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->itemPresetRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while deleting the values'),
                SPException::WARNING
            );
        }

        return $count;
    }
}