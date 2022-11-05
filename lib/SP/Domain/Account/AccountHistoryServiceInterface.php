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
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountHistoryCreateDto;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Common\Services\ServiceException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountHistoryService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountHistoryServiceInterface
{
    /**
     * Returns the item for given id
     *
     * @throws NoSuchItemException
     */
    public function getById(int $id): AccountHistoryData;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @return array Con los registros con id como clave y fecha - usuario como valor
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getHistoryForAccount(int $id): array;

    /**
     * @return ItemData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUsersByAccountId(int $id): array;

    /**
     * @return ItemData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUserGroupsByAccountId(int $id): array;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(AccountHistoryCreateDto $dto): int;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAccountsPassData(): array;

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     */
    public function delete(int $id): void;

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Deletes all the items for given accounts id
     *
     * @param  int[]  $ids
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteByAccountIdBatch(array $ids): int;

    /**
     * @throws SPException
     * @throws ConstraintException
     */
    public function updatePasswordMasterPass(AccountPasswordRequest $accountRequest): void;

    /**
     * Returns all the items
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAll(): array;
}