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

use Exception;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Infrastructure\Database\QueryResult;

/**
 * Interface CustomFieldRepository
 *
 * @template T of CustomFieldDataModel
 */
interface CustomFieldDataRepository extends Repository
{
    /**
     * Updates an item
     *
     * @param CustomFieldDataModel $customFieldData
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function update(CustomFieldDataModel $customFieldData): int;

    /**
     * Check whether the item has custom fields with data
     *
     * @param int $itemId
     * @param int $moduleId
     * @param int $definitionId
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function checkExists(int $itemId, int $moduleId, int $definitionId): bool;

    /**
     * Creates an item
     *
     * @param CustomFieldDataModel $customFieldData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create(CustomFieldDataModel $customFieldData): QueryResult;


    /**
     * Delete the module's custom field data
     *
     * @param int[] $itemIds
     * @param int $moduleId
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteBatch(array $itemIds, int $moduleId): QueryResult;

    /**
     * Returns all the items that were encrypted
     *
     * @return QueryResult<T>
     */
    public function getAllEncrypted(): QueryResult;

    /**
     * Returns the module's item for given id
     *
     * @param int $moduleId
     * @param int|null $itemId
     *
     * @return QueryResult
     * @throws Exception
     */
    public function getForModuleAndItemId(int $moduleId, ?int $itemId): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult;
}
