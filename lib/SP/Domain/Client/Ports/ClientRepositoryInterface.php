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

use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Ports\AccountFilterUserInterface;
use SP\Domain\Client\Models\Client;
use SP\Domain\Common\Ports\RepositoryInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class ClientRepository
 *
 * @template T of Client
 */
interface ClientRepositoryInterface extends RepositoryInterface
{
    /**
     * Creates an item
     *
     * @param Client $client
     *
     * @return QueryResult
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function create(Client $client): QueryResult;

    /**
     * Updates an item
     *
     * @param Client $client
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(Client $client): int;

    /**
     * Returns the item for given id
     *
     * @param int $clientId
     *
     * @return QueryResult<T>
     */
    public function getById(int $clientId): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array $clientIds
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $clientIds): QueryResult;

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
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult<T>
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * Returns the item for given name
     *
     * @param string $name
     *
     * @return QueryResult<T>
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getByName(string $name): QueryResult;

    /**
     * Return the clients visible for the current user
     *
     * @param AccountFilterUserInterface $accountFilterUser
     * @return QueryResult<T>
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllForFilter(AccountFilterUserInterface $accountFilterUser): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult;
}
