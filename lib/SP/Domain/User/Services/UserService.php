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

namespace SP\Domain\User\Services;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserPassServiceInterface;
use SP\Domain\User\Ports\UserRepositoryInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserRepository;
use SP\Util\Util;

/**
 * Class UserService
 *
 * @package SP\Domain\User\Services
 */
final class UserService extends Service implements UserServiceInterface
{
    use ServiceItemTrait;

    private UserRepository  $userRepository;
    private UserPassService $userPassService;

    public function __construct(
        Application $application,
        UserRepositoryInterface $userRepository,
        UserPassServiceInterface $userPassService
    ) {
        parent::__construct($application);

        $this->userRepository = $userRepository;
        $this->userPassService = $userPassService;
    }

    public static function mapUserLoginResponse(
        UserData $userData
    ): UserLoginResponse {
        return (new UserLoginResponse())->setId($userData->getId())
            ->setName($userData->getName())
            ->setLogin($userData->getLogin())
            ->setSsoLogin($userData->getSsoLogin())
            ->setEmail($userData->getEmail())
            ->setPass($userData->getPass())
            ->setHashSalt($userData->getHashSalt())
            ->setMPass($userData->getMPass())
            ->setMKey($userData->getMKey())
            ->setLastUpdateMPass($userData->getLastUpdateMPass())
            ->setUserGroupId($userData->getUserGroupId())
            ->setUserGroupName($userData->getUserGroupName())
            ->setUserProfileId($userData->getUserProfileId())
            ->setPreferences(self::getUserPreferences($userData->getPreferences()))
            ->setIsLdap($userData->isLdap())
            ->setIsAdminAcc($userData->isAdminAcc())
            ->setIsAdminApp($userData->isAdminApp())
            ->setIsMigrate($userData->isMigrate())
            ->setIsChangedPass($userData->isChangedPass())
            ->setIsChangePass($userData->isChangePass())
            ->setIsDisabled($userData->isDisabled())
            ->setLastUpdate((int)strtotime($userData->getLastUpdate()));
    }

    /**
     * Returns user's preferences object
     */
    public static function getUserPreferences(?string $preferences): UserPreferencesData
    {
        if (!empty($preferences)) {
            return Util::unserialize(UserPreferencesData::class, $preferences, 'SP\UserPreferences');
        }

        return new UserPreferencesData();
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function updateLastLoginById(int $id): int
    {
        $result = $this->userRepository->updateLastLoginById($id);

        if ($this->userRepository->updateLastLoginById($id) === 0) {
            throw new NoSuchItemException(__u('User does not exist'));
        }

        return $result;
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
    public function getById(int $id): UserData
    {
        $result = $this->userRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('User does not exist'));
        }

        return $result->getData();
    }

    /**
     * Returns the item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByLogin(string $login): UserData
    {
        $result = $this->userRepository->getByLogin($login);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('User does not exist'));
        }

        return $result->getData();
    }

    /**
     * Deletes an item
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): UserService
    {
        if ($this->userRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('User not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * @param  int[]  $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->userRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while deleting the users'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * Creates an item
     *
     * @throws SPException
     */
    public function createOnLogin(UserLoginRequest $userLoginRequest): int
    {
        $userData = new UserData();
        $userData->setLogin($userLoginRequest->getLogin());
        $userData->setName($userLoginRequest->getName());
        $userData->setEmail($userLoginRequest->getEmail());
        $userData->setIsLdap($userLoginRequest->getisLdap() ?? false);
        $userData->setPass($userLoginRequest->getPassword());

        $configData = $this->config->getConfigData();

        if ($userLoginRequest->getisLdap() === true) {
            $userData->setUserGroupId($configData->getLdapDefaultGroup());
            $userData->setUserProfileId($configData->getLdapDefaultProfile());
        } else {
            $userData->setUserGroupId($configData->getSsoDefaultGroup());
            $userData->setUserProfileId($configData->getSsoDefaultProfile());
        }

        return $this->create($userData);
    }

    /**
     * Creates an item
     *
     * @throws SPException
     */
    public function create(UserData $itemData): int
    {
        $itemData->setPass(Hash::hashKey($itemData->getPass()));

        return $this->userRepository->create($itemData);
    }

    /**
     * Creates an item
     *
     * @throws SPException
     * @throws CryptoException
     */
    public function createWithMasterPass(UserData $itemData, string $userPass, string $masterPass): int
    {
        $response = $this->userPassService->createMasterPass(
            $masterPass,
            $itemData->getLogin(),
            $userPass
        );

        $itemData->setMPass($response->getCryptMasterPass());
        $itemData->setMKey($response->getCryptSecuredKey());
        $itemData->setLastUpdateMPass(time());
        $itemData->setPass($userPass);

        return $this->create($itemData);
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
    public function update(UserData $userData): void
    {
        $update = $this->userRepository->update($userData);

        if ($update === 0) {
            throw new ServiceException(__u('Error while updating the user'));
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
        $passRequest = new UpdatePassRequest(Hash::hashKey($pass));
        $passRequest->setIsChangePass(false);
        $passRequest->setIsChangedPass(true);

        $updatePassById = $this->userRepository->updatePassById(
            $userId,
            $passRequest
        );

        if ($updatePassById === 0) {
            throw new ServiceException(__u('Error while updating the password'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePreferencesById(int $userId, UserPreferencesData $userPreferencesData): int
    {
        return $this->userRepository->updatePreferencesById(
            $userId,
            $userPreferencesData
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(UserLoginRequest $userLoginRequest): int
    {
        $userData = new UserData();
        $userData->setLogin($userLoginRequest->getLogin());
        $userData->setName($userLoginRequest->getName());
        $userData->setEmail($userLoginRequest->getEmail());
        $userData->setIsLdap($userLoginRequest->getisLdap());
        $userData->setPass(Hash::hashKey($userLoginRequest->getPassword()));

        return $this->userRepository->updateOnLogin($userData);
    }

    /**
     * Get all items from the service's repository
     *
     * @return UserData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->userRepository->getBasicInfo()->getDataAsArray();
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailForGroup(int $groupId): array
    {
        return $this->userRepository->getUserEmailForGroup($groupId)->getDataAsArray();
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @throws ConstraintException
     * @throws QueryException
     *
     * @TODO create unit test
     */
    public function getUserEmailForAll(): array
    {
        return $this->userRepository->getUserEmail()->getDataAsArray();
    }


    /**
     * Return the email of the given user's id
     *
     * @param  int[]  $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @TODO create unit test
     */
    public function getUserEmailById(array $ids): array
    {
        return $this->userRepository->getUserEmailById($ids)->getDataAsArray();
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
