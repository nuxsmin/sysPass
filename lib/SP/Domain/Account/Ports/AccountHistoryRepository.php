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

namespace SP\Domain\Account\Ports;

use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountHistoryRepository
 *
 * @package Services
 */
interface AccountHistoryRepository extends Repository
{
    /**
     * Creates an item
     *
     * @param AccountHistoryCreateDto $dto
     */
    public function create(AccountHistoryCreateDto $dto): int;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param int $id
     *
     * @return QueryResult
     */
    public function getHistoryForAccount(int $id): QueryResult;

    /**
     * Deletes all the items for given accounts id
     *
     * @param array $ids
     *
     * @return int
     */
    public function deleteByAccountIdBatch(array $ids): int;

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return QueryResult
     */
    public function getAccountsPassData(): QueryResult;

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param int $accountId
     * @param EncryptedPassword $encryptedPassword
     *
     * @return bool
     */
    public function updatePassword(int $accountId, EncryptedPassword $encryptedPassword): bool;

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     */
    public function getById(int $id): QueryResult;

    /**
     * Returns all the items
     *
     * @return QueryResult
     */
    public function getAll(): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult;

}
