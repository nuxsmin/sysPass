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

namespace SP\Domain\User\Ports;

use SP\DataModel\User;
use SP\DataModel\UserPreferencesData;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Services\UpdatePassRequest;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserRepository
 *
 * @package SP\Infrastructure\User\Repositories
 */
interface UserRepository extends Repository
{
    /**
     * Updates an user's pass
     *
     * @param  int  $id
     * @param  UpdatePassRequest  $passRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassById(int $id, UpdatePassRequest $passRequest): int;

    /**
     * @param $login string
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByLogin(string $login): QueryResult;

    /**
     * Returns items' basic information
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getBasicInfo(): QueryResult;

    /**
     * Updates user's master password
     *
     * @param  int  $id
     * @param  string  $pass
     * @param  string  $key
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateMasterPassById(int $id, string $pass, string $key): int;

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $id int El id del usuario
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function updateLastLoginById(int $id): int;

    /**
     * @param  string  $login
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkExistsByLogin(string $login): bool;

    /**
     * @param User $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(User $itemData): int;

    /**
     * Updates an user's pass
     *
     * @param  int  $id
     * @param  UserPreferencesData  $userPreferencesData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePreferencesById(int $id, UserPreferencesData $userPreferencesData): int;

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @param  int  $groupId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailForGroup(int $groupId): QueryResult;

    /**
     * Obtener el email de los usuarios
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmail(): QueryResult;

    /**
     * Return the email of the given user's id
     *
     * @param  int[]  $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailById(array $ids): QueryResult;

    /**
     * Returns the usage of the given user's id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageForUser(int $id): QueryResult;
}
