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

namespace SP\Domain\Client\Ports;


use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Client\Models\Client;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class ClientService
 *
 * @package SP\Domain\Client\Services
 */
interface ClientServiceInterface
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): Client;

    /**
     * Returns the item for given name
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByName(string $name): ?Client;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): ClientServiceInterface;

    /**
     * @param  int[]  $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function create($itemData): int;

    /**
     * @param Client $itemData
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(Client $itemData): int;

    /**
     * Get all items from the service's repository
     *
     * @return Client[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * Returns all clients visible for a given user
     *
     * @return ItemData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllForUser(): array;
}
