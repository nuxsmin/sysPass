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

namespace SP\Domain\Auth\Ports;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\DuplicatedItemException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Database\Dtos\QueryResult;

/**
 * Class AuthTokenService
 *
 * @template T of AuthTokenModel
 */
interface AuthTokenService
{
    /**
     * @return QueryResult<T>
     * @throws QueryException
     * @throws ConstraintException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws UnauthorizedActionException
     */
    public function getById(int $id): AuthTokenModel;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws UnauthorizedActionException
     */
    public function delete(int $id): void;

    /**
     * Deletes the given token IDs with per-token atomic outcomes.
     *
     * @param int[] $ids
     * @return array{deleted: int[], failed: array<int, string>}
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): array;

    /**
     * @throws SPException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AuthTokenModel $authToken): int;

    public function getLastGeneratedToken(): ?string;

    /**
     * @throws Exception
     */
    public function refreshAndUpdate(AuthTokenModel $authToken): void;

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws UnauthorizedActionException
     */
    public function update(AuthTokenModel $authToken): void;

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateRaw(AuthTokenModel $authToken): void;

    /**
     * Devolver los datos de un token
     *
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function getTokenByToken(int $actionId, string $token): AuthTokenModel;

    /**
     * @return T[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;
}
