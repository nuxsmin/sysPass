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

namespace SP\Domain\User\Services;

use JsonException;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Dtos\UserLoginRequest;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Ports\UserMasterPassService;
use SP\Domain\User\Ports\UserRepository;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class UserService
 */
final class User extends Service implements UserService
{
    public function __construct(
        Application                            $application,
        private readonly UserRepository        $userRepository,
        private readonly UserMasterPassService $userMasterPassService
    ) {
        parent::__construct($application);
    }

    /**
     * Update the last user log in
     *
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function updateLastLoginById(int $id): void
    {
        if ($this->userRepository->updateLastLoginById($id) === 0) {
            throw NoSuchItemException::info(__u('User not found'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkExistsByLogin(string $login): bool
    {
        return $this->userRepository->checkExistsByLogin($login);
    }

    /**
     * Returns the item for given id
     *
     * @throws SPException
     */
    public function getById(int $id): UserModel
    {
        $result = $this->userRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::error(__u('User does not exist'));
        }

        return $result->getData(UserModel::class);
    }

    /**
     * Returns the item for given login
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByLogin(string $login): UserModel
    {
        $result = $this->userRepository->getByLogin($login);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::error(__u('User not found'));
        }

        return $result->getData(UserModel::class);
    }

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void
    {
        if ($this->userRepository->delete($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('User not found'));
        }
    }

    /**
     * @param int[] $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->userRepository->deleteByIdBatch($ids)->getAffectedNumRows();

        if ($count !== count($ids)) {
            throw ServiceException::warning(__u('Error while deleting the users'));
        }

        return $count;
    }

    /**
     * Creates an item
     *
     * @param UserLoginRequest $userLoginRequest
     * @return int
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function createOnLogin(UserLoginRequest $userLoginRequest): int
    {
        $userData = [
            'login' => $userLoginRequest->getLogin(),
            'name' => $userLoginRequest->getName(),
            'email' => $userLoginRequest->getEmail(),
            'isLdap' => $userLoginRequest->getisLdap() ?? false,
            'pass' => $userLoginRequest->getPassword()

        ];

        $configData = $this->config->getConfigData();

        if ($userLoginRequest->getisLdap() === true) {
            $userData['userGroupId'] = $configData->getLdapDefaultGroup();
            $userData['userProfileId'] = $configData->getLdapDefaultProfile();
        } else {
            $userData['userGroupId'] = $configData->getSsoDefaultGroup();
            $userData['userProfileId'] = $configData->getSsoDefaultProfile();
        }

        return $this->create(new UserModel($userData));
    }

    /**
     * @inheritDoc
     */
    public function create(UserModel $user): int
    {
        return $this->userRepository
            ->create($user->mutate(['pass' => Hash::hashKey($user->getPass())]))
            ->getLastId();
    }

    /**
     * Creates an item
     *
     * @param UserModel $user
     * @param string $userPass
     * @param string $masterPass
     * @return int
     * @throws SPException
     * @throws ServiceException
     */
    public function createWithMasterPass(UserModel $user, string $userPass, string $masterPass): int
    {
        $response = $this->userMasterPassService->create(
            $masterPass,
            $user->getLogin(),
            $userPass
        );

        return $this->create(
            $user->mutate(
                [
                    'mPass' => $response->getCryptMasterPass(),
                    'mKey' => $response->getCryptSecuredKey(),
                    'lastUpdateMPass' => time(),
                    'pass' => $userPass
                ]
            )
        );
    }

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $searchData): QueryResult
    {
        return $this->userRepository->search($searchData);
    }

    /**
     * Updates an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function update(UserModel $user): void
    {
        if ($this->userRepository->update($user) === 0) {
            throw ServiceException::error(__u('Error while updating the user'));
        }
    }

    /**
     * Updates a user's pass
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function updatePass(int $userId, string $pass): void
    {
        $user = new UserModel(
            [
                'id' => $userId,
                'pass' => Hash::hashKey($pass),
                'isChangePass' => false,
                'isChangedPass' => true
            ]
        );

        if ($this->userRepository->updatePassById($user) === 0) {
            throw ServiceException::error(__u('Error while updating the password'));
        }
    }

    /**
     * @param int $userId
     * @param UserPreferences $userPreferences
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws JsonException
     */
    public function updatePreferencesById(int $userId, UserPreferences $userPreferences): int
    {
        return $this->userRepository->updatePreferencesById($userId, $userPreferences);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(UserLoginRequest $userLoginRequest): int
    {
        $userData = [
            'login' => $userLoginRequest->getLogin(),
            'name' => $userLoginRequest->getName(),
            'email' => $userLoginRequest->getEmail(),
            'isLdap' => $userLoginRequest->getisLdap(),
            'pass' => Hash::hashKey($userLoginRequest->getPassword())

        ];

        return $this->userRepository->updateOnLogin(new UserModel($userData));
    }

    /**
     * Get all items from the service's repository
     *
     * @return array<UserModel>
     */
    public function getAll(): array
    {
        return $this->userRepository->getAll()->getDataAsArray(UserModel::class);
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailForGroup(int $groupId): array
    {
        return $this->userRepository->getUserEmailForGroup($groupId)->getDataAsArray(UserModel::class);
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @return array<UserModel>
     */
    public function getUserEmailForAll(): array
    {
        return $this->userRepository->getUserEmail()->getDataAsArray(UserModel::class);
    }


    /**
     * Return the email of the given user's id
     *
     * @param int[] $ids
     *
     * @return array<UserModel>
     */
    public function getUserEmailById(array $ids): array
    {
        return $this->userRepository->getUserEmailById($ids)->getDataAsArray(UserModel::class);
    }

    /**
     * Returns the usage of the given user's id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageForUser(int $id): array
    {
        return $this->userRepository->getUsageForUser($id)->getDataAsArray();
    }
}
