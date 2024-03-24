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

namespace SP\Domain\Account\Ports;

use SP\DataModel\File;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountFileRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
interface AccountFileRepository extends Repository
{
    /**
     * Creates an item
     *
     * @param File $fileData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(File $fileData): int;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByAccountId(int $id): QueryResult;

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): bool;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     */
    public function getById(int $id): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;
}
