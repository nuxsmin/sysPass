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

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Domain\Account\Services\PublicLinkKey;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PublicLinkService
 *
 * @package SP\Domain\Common\Services\PublicLink
 */
interface PublicLinkServiceInterface
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getById(int $id): PublicLinkListData;

    /**
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function refresh(int $id): bool;

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function getPublicLinkKey(?string $hash = null): PublicLinkKey;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): PublicLinkServiceInterface;

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * @throws SPException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PublicLinkData $itemData): int;

    /**
     * Get all items from the service's repository
     *
     * @return PublicLinkListData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array;

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData): void;

    /**
     * @throws SPException
     */
    public function getByHash(string $hash): PublicLinkData;

    /**
     * Devolver el hash asociado a un elemento
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getHashForItem(int $itemId): PublicLinkData;

    /**
     * Updates an item
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PublicLinkData $itemData): void;
}
