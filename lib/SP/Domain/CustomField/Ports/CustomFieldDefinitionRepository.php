<?php
declare(strict_types=1);
/**
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
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldDefRepository
 *
 * @template T of CustomFieldDefinitionModel
 */
interface CustomFieldDefinitionRepository extends Repository
{
    /**
     * Creates an item
     *
     * @param CustomFieldDefinitionModel $customFieldDefinition
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(CustomFieldDefinitionModel $customFieldDefinition): QueryResult;

    /**
     * Updates an item
     *
     * @param CustomFieldDefinitionModel $customFieldDefinition
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(CustomFieldDefinitionModel $customFieldDefinition): int;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<T>
     */
    public function getById(int $id): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): QueryResult;

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;
}
