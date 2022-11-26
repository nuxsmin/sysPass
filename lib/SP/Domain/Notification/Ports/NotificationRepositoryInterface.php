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

namespace SP\Domain\Notification\Ports;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class NotificationRepository
 *
 * @package SP\Infrastructure\Notification\Repositories
 */
interface NotificationRepositoryInterface extends \SP\Domain\Common\Ports\RepositoryInterface
{
    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteAdmin(int $id): int;

    /**
     * Deletes an item
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteAdminBatch(array $ids): int;

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForUserId(ItemSearchData $itemSearchData, int $userId): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchForAdmin(ItemSearchData $itemSearchData, int $userId): QueryResult;

    /**
     * Marcar una notificación como leída
     *
     * @param  int  $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function setCheckedById(int $id): int;

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @param  string  $component
     * @param  int  $userId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getForUserIdByDate(string $component, int $userId): QueryResult;

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAllForUserId(int $id): QueryResult;

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAllActiveForUserId(int $id): QueryResult;

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAllActiveForAdmin(int $id): QueryResult;
}
