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

namespace SP\Domain\ItemPreset\Services;

use SP\Core\Application;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Models\ItemPreset;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\ItemPreset\Ports\ItemPresetRepository;
use SP\Domain\ItemPreset\Ports\ItemPresetServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\ItemPreset\Repositories\ItemPresetBaseRepository;

/**
 * Class ItemPresetService
 *
 * @package SP\Domain\Account\Services
 */
final class ItemPresetService extends Service implements ItemPresetServiceInterface
{
    private ItemPresetBaseRepository $itemPresetRepository;

    public function __construct(Application $application, ItemPresetRepository $itemPresetRepository)
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
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
    public function getById(int $id): ItemPreset
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
     * @return ItemPreset[]
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
    public function getForCurrentUser(string $type): ?ItemPreset
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
    ): ?ItemPreset {
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
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
