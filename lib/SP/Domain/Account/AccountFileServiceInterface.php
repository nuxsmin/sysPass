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

namespace SP\Domain\Account;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidImageException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountFileService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountFileServiceInterface
{
    /**
     * Creates an item
     *
     * @throws InvalidImageException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(FileData $itemData): int;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getInfoById(int $id): ?FileExtData;

    /**
     * Returns the item for given id
     *
     * @return mixed|null
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById(int $id);

    /**
     * Returns all the items
     *
     * @return FileExtData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * Returns all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @return FileExtData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): array;

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Deletes an item
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): AccountFileServiceInterface;

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $searchData): QueryResult;

    /**
     * Returns the item for given id
     *
     * @return FileData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByAccountId(int $id): array;
}