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

namespace SP\Domain\Auth\Ports;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

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
     * @throws QueryException
     */
    public function getById(int $id): AuthTokenModel;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void;

    /**
     * Deletes all the items for given ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): void;

    /**
     * @throws SPException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AuthTokenModel $authToken): int;

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
