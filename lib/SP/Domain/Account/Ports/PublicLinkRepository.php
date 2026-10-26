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

namespace SP\Domain\Account\Ports;

use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PublicLinkRepository
 *
 * @template T of PublicLinkModel
 */
interface PublicLinkRepository extends Repository
{
    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): void;

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
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<T>
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * Creates an item
     *
     * @param PublicLink $publicLinkData
     *
     * @return QueryResult
     * @throws DuplicatedItemException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create(PublicLink $publicLinkData): QueryResult;

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLink $publicLinkData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addLinkView(PublicLink $publicLinkData): bool;

    /**
     * Updates an item
     *
     * @param PublicLink $publicLinkData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PublicLink $publicLinkData): bool;

    /**
     * Refreshes a public link
     *
     * @param PublicLink $publicLinkData
     *
     * @return bool
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function refresh(PublicLink $publicLinkData): bool;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<T>
     */
    public function getById(int $id): QueryResult;

    /**
     * @param $hash string
     *
     * @return QueryResult<T>
     */
    public function getByHash(string $hash): QueryResult;

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     *
     * @return QueryResult
     */
    public function getHashForItem(int $itemId): QueryResult;
}
