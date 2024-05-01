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

namespace SP\Domain\User\Ports;

use Defuse\Crypto\Exception\CryptoException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Dtos\UserLoginRequest;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserPreferences;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserService
 *
 * @package SP\Domain\User\Services
 */
interface UserService
{
    /**
     * Update the last user log in
     *
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function updateLastLoginById(int $id): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkExistsByLogin(string $login): bool;

    /**
     * Returns the item for given id
     *
     * @throws SPException
     */
    public function getById(int $id): UserModel;

    /**
     * Returns the item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByLogin(string $login): UserModel;

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void;

    /**
     * @param int[] $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int;

    /**
     * Creates an item
     *
     * @param UserLoginRequest $userLoginRequest
     * @return int
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function createOnLogin(UserLoginRequest $userLoginRequest): int;

    /**
     * Creates an item
     *
     * @param UserModel $user
     * @return int
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function create(UserModel $user): int;

    /**
     * Creates an item
     *
     * @throws SPException
     * @throws CryptoException
     */
    public function createWithMasterPass(UserModel $user, string $userPass, string $masterPass): int;

    /**
     * Searches for items by a given filter
     *
     * @return QueryResult<UserModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $searchData): QueryResult;

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function update(UserModel $user): void;

    /**
     * Updates a user's pass
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function updatePass(int $userId, string $pass): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePreferencesById(int $userId, UserPreferences $userPreferences): int;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(UserLoginRequest $userLoginRequest): int;

    /**
     * Get all items from the service's repository
     *
     * @return array<UserModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailForGroup(int $groupId): array;

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @throws ConstraintException
     * @throws QueryException
     **/
    public function getUserEmailForAll(): array;

    /**
     * Return the email of the given user's id
     *
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailById(array $ids): array;

    /**
     * Returns the usage of the given user's id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageForUser(int $id): array;
}
