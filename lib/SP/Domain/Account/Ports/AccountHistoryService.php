<?php
declare(strict_types=1);
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

namespace SP\Domain\Account\Ports;

use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\AccountHistory;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountHistoryService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountHistoryService
{
    /**
     * Returns the item for given id
     *
     * @throws NoSuchItemException
     */
    public function getById(int $id): AccountHistory;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @return array Con los registros con id como clave y fecha - usuario como valor
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getHistoryForAccount(int $id): array;

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @throws ConstraintException
     * @throws QueryException
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
     * @param int[] $ids
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Deletes all the items for given accounts id
     *
     * @param int[] $ids
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
    public function updatePasswordMasterPass(int $accountId, EncryptedPassword $encryptedPassword): void;
}
