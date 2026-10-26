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

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Domain\Account\Dtos\PublicLinkKey;
use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Account\Models\PublicLinkList;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PublicLinkService
 *
 * @package SP\Domain\Common\Services\PublicLink
 */
interface PublicLinkService
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchDto): QueryResult;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): PublicLinkList;

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function refresh(int $id): bool;

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function getPublicLinkKey(?string $hash = null): PublicLinkKey;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): PublicLinkService;

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * @throws SPException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PublicLink $publicLink): int;

    /**
     * Get all items from the service's repository
     *
     * @return PublicLinkList[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addLinkView(PublicLink $publicLink): void;

    /**
     * @throws SPException
     */
    public function getByHash(string $hash): PublicLink;

    /**
     * Devolver el hash asociado a un elemento
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getHashForItem(int $id): Simple;

    /**
     * Updates an item
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PublicLink $publicLink): void;
}
