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
use SP\Domain\Common\Ports\RepositoryInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountToUserGroupRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
interface AccountToUserGroupRepositoryInterface extends RepositoryInterface
{
    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param  int  $id  con el Id de la cuenta
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserGroupsByAccountId(int $id): QueryResult;

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUserGroupsByUserGroupId(int $id): QueryResult;

    /**
     * @param $id int
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByUserGroupId(int $id): bool;

    /**
     * @param  int  $id
     * @param  bool  $isEdit
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteTypeByAccountId(int $id, bool $isEdit): void;

    /**
     * @param  int  $accountId
     * @param  array  $items
     * @param  bool  $isEdit
     *
     * @return void
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addByType(int $accountId, array $items, bool $isEdit): void;

    /**
     * @param $id int
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId(int $id): bool;
}
