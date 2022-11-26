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

namespace SP\Domain\Account\Ports;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PublicLinkRepository
 *
 * @package SP\Infrastructure\Common\Repositories\PublicLink
 */
interface PublicLinkRepositoryInterface
{
    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return void
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): void;

    /**
     * Returns all the items
     *
     * @return QueryResult
     */
    public function getAll(): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * Creates an item
     *
     * @param  PublicLinkData  $publicLinkData
     *
     * @return QueryResult
     * @throws DuplicatedItemException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create(PublicLinkData $publicLinkData): QueryResult;

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param  PublicLinkData  $publicLinkData
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData): bool;

    /**
     * Updates an item
     *
     * @param  \SP\DataModel\PublicLinkData  $publicLinkData
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(PublicLinkData $publicLinkData): bool;

    /**
     * Refreshes a public link
     *
     * @param  PublicLinkData  $publicLinkData
     *
     * @return bool
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function refresh(PublicLinkData $publicLinkData): bool;

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getById(int $id): QueryResult;

    /**
     * @param $hash string
     *
     * @return QueryResult
     */
    public function getByHash(string $hash): QueryResult;

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param  int  $itemId
     *
     * @return QueryResult
     */
    public function getHashForItem(int $itemId): QueryResult;
}
