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

namespace SP\Domain\User\Ports;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserPassRecoverRepository
 *
 * @package SP\Infrastructure\Common\Repositories\UserPassRecover
 */
interface UserPassRecoverRepositoryInterface
{
    /**
     * Checks recovery limit attempts by user's id and time
     *
     * @param  int  $userId
     * @param  int  $time
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAttemptsByUserId(int $userId, int $time): int;

    /**
     * Adds a hash for a user's id
     *
     * @param  int  $userId
     * @param  string  $hash
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $userId, string $hash): int;

    /**
     * Toggles a hash used
     *
     * @param  string  $hash
     * @param  int  $time
     *
     * @return int
     * @throws SPException
     */
    public function toggleUsedByHash(string $hash, int $time): int;

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param  string  $hash
     * @param  int  $time
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUserIdForHash(string $hash, int $time): QueryResult;
}
