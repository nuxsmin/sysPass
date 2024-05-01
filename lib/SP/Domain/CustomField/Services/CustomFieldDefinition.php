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

namespace SP\Domain\CustomField\Services;

use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionRepository;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class CustomFieldDefinition
 *
 * @template T of CustomFieldDefinitionModel
 */
final class CustomFieldDefinition extends Service implements CustomFieldDefinitionService
{

    public function __construct(
        Application                                      $application,
        private readonly CustomFieldDefinitionRepository $customFieldDefinitionRepository
    ) {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->customFieldDefinitionRepository->search($itemSearchData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void
    {
        $this->customFieldDefinitionRepository->transactionAware(
            function () use ($ids) {
                $affectedNumRows = $this->customFieldDefinitionRepository->deleteByIdBatch($ids)->getAffectedNumRows();

                if ($affectedNumRows === 0) {
                    throw ServiceException::warning(__u('Error while deleting the fields'));
                }
            },
            $this
        );
    }

    /**
     * @param CustomFieldDefinitionModel $customFieldDefinition
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(CustomFieldDefinitionModel $customFieldDefinition): void
    {
        $this->customFieldDefinitionRepository->update($customFieldDefinition);
    }

    /**
     * @param int $id
     * @return CustomFieldDefinitionModel
     * @throws NoSuchItemException
     */
    public function getById(int $id): CustomFieldDefinitionModel
    {
        $result = $this->customFieldDefinitionRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Field not found'));
        }

        return $result->getData(CustomFieldDefinitionModel::class);
    }

    /**
     * @throws ServiceException
     */
    public function changeModule(CustomFieldDefinitionModel $customFieldDefinition): int
    {
        return $this->customFieldDefinitionRepository->transactionAware(
            function () use ($customFieldDefinition) {
                $this->customFieldDefinitionRepository->delete($customFieldDefinition->getId());

                return $this->customFieldDefinitionRepository->create($customFieldDefinition);
            },
            $this
        );
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function delete(int $id): void
    {
        if ($this->customFieldDefinitionRepository->delete($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Field not found'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(CustomFieldDefinitionModel $customFieldDefinition): int
    {
        return $this->customFieldDefinitionRepository->create($customFieldDefinition)->getLastId();
    }

    /**
     * Get all items
     *
     * @return T[]
     */
    public function getAll(): array
    {
        return $this->customFieldDefinitionRepository->getAll()->getDataAsArray(CustomFieldDefinitionModel::class);
    }
}
