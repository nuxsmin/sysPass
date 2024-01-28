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

namespace SP\Domain\CustomField\Ports;

use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldDefinition;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldDefService
 *
 * @package SP\Domain\CustomField\Services
 */
interface CustomFieldDefinitionService
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function delete(int $id): void;

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(CustomFieldDefinitionModel $customFieldDefinition): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function update(CustomFieldDefinitionModel $customFieldDefinition): void;

    /**
     * @param int $id
     * @return CustomFieldDefinitionModel
     * @throws NoSuchItemException
     */
    public function getById(int $id): CustomFieldDefinitionModel;

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateRaw(CustomFieldDefinitionModel $customFieldDefinition): void;

    /**
     * Get all items from the service's repository
     *
     * @return CustomFieldDefinition[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * @throws ServiceException
     */
    public function changeModule(CustomFieldDefinitionModel $customFieldDefinition): int;
}
