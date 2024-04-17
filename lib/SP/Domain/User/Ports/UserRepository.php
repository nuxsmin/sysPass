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

use Exception;
use JsonException;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserPreferences;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserRepository
 *
 * @template T of UserModel
 */
interface UserRepository extends Repository
{
    /**
     * Updates an item
     *
     * @param UserModel $user
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(UserModel $user): int;

    /**
     * Updates a user's pass
     *
     * @param UserModel $user
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassById(UserModel $user): int;

    /**
     * @param $login string
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function getByLogin(string $login): QueryResult;

    /**
     * Returns items' basic information
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult;

    /**
     * Updates user's master password
     *
     * @param int $id
     * @param string $pass
     * @param string $key
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
     * @param string $login
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkExistsByLogin(string $login): bool;

    /**
     * @param UserModel $user
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(UserModel $user): int;

    /**
     * Updates an user's pass
     *
     * @param int $id
     * @param UserPreferences $userPreferences
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws JsonException
     */
    public function updatePreferencesById(int $id, UserPreferences $userPreferences): int;

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @param int $groupId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function getUserEmailForGroup(int $groupId): QueryResult;

    /**
     * Obtener el email de los usuarios
     *
     * @return QueryResult<T>
     */
    public function getUserEmail(): QueryResult;

    /**
     * Return the email of the given user's id
     *
     * @param array<int> $ids
     *
     * @return QueryResult<T>
     */
    public function getUserEmailById(array $ids): QueryResult;

    /**
     * Returns the usage of the given user's id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function getUsageForUser(int $id): QueryResult;

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<T>
     * @throws QueryException
     * @throws ConstraintException
     * @throws Exception
     */
    public function getById(int $id): QueryResult;

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): QueryResult;

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     * @throws Exception
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * Creates an item
     *
     * @param UserModel $user
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function create(UserModel $user): QueryResult;
}
