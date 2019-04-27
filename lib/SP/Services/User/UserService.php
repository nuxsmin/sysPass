<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Services\User;

use Defuse\Crypto\Exception\CryptoException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\User\UserRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;
use SP\Util\Util;

/**
 * Class UserService
 *
 * @package SP\Services\User
 */
final class UserService extends Service
{
    use ServiceItemTrait;

    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var UserPassService
     */
    protected $userPassService;

    /**
     * @param UserData $userData
     *
     * @return UserLoginResponse
     */
    public static function mapUserLoginResponse(UserData $userData)
    {
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
     *
     * @param string $preferences
     *
     * @return UserPreferencesData
     */
    public static function getUserPreferences($preferences)
    {
        if (!empty($preferences)) {
            return Util::unserialize(UserPreferencesData::class, $preferences, 'SP\UserPreferences');
        }

        return new UserPreferencesData();
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $id int El id del usuario
     *
     * @return int
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function updateLastLoginById($id)
    {
        $result = $this->userRepository->updateLastLoginById($id);

        if ($this->userRepository->updateLastLoginById($id) === 0) {
            throw new NoSuchItemException(__u('User does not exist'));
        }

        return $result;
    }

    /**
     * @param $login
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkExistsByLogin($login)
    {
        return $this->userRepository->checkExistsByLogin($login);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return UserData
     * @throws SPException
     */
    public function getById($id)
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
     * @param $login
     *
     * @return UserData
     * @throws SPException
     */
    public function getByLogin($login)
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
     * @param $id
     *
     * @return UserService
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete($id)
    {
        if ($this->userRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('User not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     *
     * @return int
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->userRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the users'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * Creates an item
     *
     * @param UserLoginRequest $userLoginRequest
     *
     * @return int
     * @throws SPException
     */
    public function createOnLogin(UserLoginRequest $userLoginRequest)
    {
        $userData = new UserData();
        $userData->setLogin($userLoginRequest->getLogin());
        $userData->setName($userLoginRequest->getName());
        $userData->setEmail($userLoginRequest->getEmail());
        $userData->setIsLdap($userLoginRequest->getisLdap());
        $userData->setPass($userLoginRequest->getPassword());

        $configData = $this->config->getConfigData();

        if ($userLoginRequest->getisLdap() === 1) {
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
     * @param UserData $itemData
     *
     * @return int
     * @throws SPException
     */
    public function create(UserData $itemData)
    {
        $itemData->setPass(Hash::hashKey($itemData->getPass()));

        return $this->userRepository->create($itemData);
    }

    /**
     * Creates an item
     *
     * @param UserData $itemData
     * @param string   $userPass
     * @param string   $masterPass
     *
     * @return int
     * @throws SPException
     * @throws CryptoException
     */
    public function createWithMasterPass(UserData $itemData, $userPass, $masterPass)
    {
        $response = $this->userPassService->createMasterPass($masterPass, $itemData->getLogin(), $userPass);

        $itemData->setMPass($response->getCryptMasterPass());
        $itemData->setMKey($response->getCryptSecuredKey());
        $itemData->setLastUpdateMPass(time());
        $itemData->setPass($userPass);

        return $this->create($itemData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $SearchData)
    {
        return $this->userRepository->search($SearchData);
    }

    /**
     * Updates an item
     *
     * @param UserData $itemData
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function update($itemData)
    {
        if ($this->userRepository->update($itemData) === 0) {
            throw new ServiceException(__u('Error while updating the user'));
        }
    }

    /**
     * Updates an user's pass
     *
     * @param int    $userId
     * @param string $pass
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function updatePass($userId, $pass)
    {
        $passRequest = new UpdatePassRequest(Hash::hashKey($pass));
        $passRequest->setIsChangePass(0);
        $passRequest->setIsChangedPass(1);

        if ($this->userRepository->updatePassById($userId, $passRequest) === 0) {
            throw new ServiceException(__u('Error while updating the password'));
        }
    }

    /**
     * @param                     $userId
     * @param UserPreferencesData $userPreferencesData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePreferencesById($userId, UserPreferencesData $userPreferencesData)
    {
        return $this->userRepository->updatePreferencesById($userId, $userPreferencesData);
    }

    /**
     * @param UserLoginRequest $userLoginRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(UserLoginRequest $userLoginRequest)
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
    public function getAllBasic()
    {
        return $this->userRepository->getBasicInfo()->getDataAsArray();
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @param $groupId
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailForGroup($groupId)
    {
        return $this->userRepository->getUserEmailForGroup($groupId)->getDataAsArray();
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     *
     * @TODO create unit test
     */
    public function getUserEmailForAll()
    {
        return $this->userRepository->getUserEmail()->getDataAsArray();
    }


    /**
     * Return the email of the given user's id
     *
     * @param array $ids
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     * @TODO create unit test
     */
    public function getUserEmailById(array $ids)
    {
        return $this->userRepository->getUserEmailById($ids)->getDataAsArray();
    }

    /**
     * Returns the usage of the given user's id
     *
     * @param int $id
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageForUser($id)
    {
        return $this->userRepository->getUsageForUser($id)->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->userRepository = $this->dic->get(UserRepository::class);
        $this->userPassService = $this->dic->get(UserPassService::class);
    }
}