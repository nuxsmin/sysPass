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
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Domain\ItemPreset\Ports\ItemPresetRepository;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class ItemPreset
 *
 * @template T of ItemPresetModel
 */
final class ItemPreset extends Service implements ItemPresetService
{

    public function __construct(Application $application, private readonly ItemPresetRepository $itemPresetRepository)
    {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ItemPresetModel $itemPreset): int
    {
        return $this->itemPresetRepository->create($itemPreset)->getLastId();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ItemPresetModel $itemPreset): int
    {
        return $this->itemPresetRepository->update($itemPreset);
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
        if ($this->itemPresetRepository->delete($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::error(__u('Value not found'));
        }

        return $this;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return ItemPresetModel
     * @throws NoSuchItemException
     */
    public function getById(int $id): ItemPresetModel
    {
        $result = $this->itemPresetRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::error(__u('Value not found'));
        }

        return $result->getData(ItemPresetModel::class);
    }

    /**
     * Returns all the items
     *
     * @return array<T>
     */
    public function getAll(): array
    {
        return $this->itemPresetRepository->getAll()->getDataAsArray(ItemPresetModel::class);
    }

    /**
     * Searches for items by a given filter
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->itemPresetRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForCurrentUser(string $type): ?ItemPresetModel
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
    public function getForUser(string $type, int $userId, int $userGroupId, int $userProfileId): ?ItemPresetModel
    {
        return $this->itemPresetRepository->getByFilter(
            $type,
            $userId,
            $userGroupId,
            $userProfileId
        )->getData(ItemPresetModel::class);
    }

    /**
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->itemPresetRepository->deleteByIdBatch($ids)->getAffectedNumRows();

        if ($count !== count($ids)) {
            throw ServiceException::warning(__u('Error while deleting the values'));
        }

        return $count;
    }
}
