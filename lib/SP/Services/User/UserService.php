<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Repositories\User\UserRepository;
use SP\Services\ServiceItemTrait;
use SP\Util\Util;

/**
 * Class UserService
 *
 * @package SP\Services\User
 */
class UserService
{
    use InjectableTrait;
    use ServiceItemTrait;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * UserService constructor.
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param UserRepository $userRepository
     */
    public function inject(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $id int El id del usuario
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function updateLastLoginById($id)
    {
        return $this->userRepository->updateLastLoginById($id);
    }

    /**
     * @param $login
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkExistsByLogin($login)
    {
        return $this->userRepository->checkExistsByLogin($login);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     * @throws SPException
     */
    public function getById($id)
    {
        return $this->userRepository->getById($id);
    }

    /**
     * Returns the item for given id
     *
     * @param $login
     * @return UserLoginResponse
     * @throws SPException
     */
    public function getByLogin($login)
    {
        $userData = $this->userRepository->getByLogin($login);

        $userLoginResponse = new UserLoginResponse();
        $userLoginResponse->setId($userData->getId())
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
            ->setUserProfileId($userData->getUserProfileId())
            ->setPreferences(self::getUserPreferences($userData->getPreferences()))
            ->setIsLdap($userData->isIsLdap())
            ->setIsAdminAcc($userData->isIsAdminAcc())
            ->setIsAdminApp($userData->isIsAdminApp())
            ->setIsMigrate($userData->isIsMigrate())
            ->setIsChangedPass($userData->isIsChangedPass())
            ->setIsChangePass($userData->isIsChangePass())
            ->setIsDisabled($userData->isIsDisabled());

        return $userLoginResponse;
    }

    /**
     * Returns user's preferences object
     *
     * @param string $preferences
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
     * Deletes an item
     *
     * @param $id
     * @return UserRepository
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        return $this->userRepository->delete($id);
    }

    /**
     * Creates an item
     *
     * @param UserLoginRequest $userLoginRequest
     * @return mixed
     * @throws SPException
     */
    public function createOnLogin(UserLoginRequest $userLoginRequest)
    {
        $userData = new UserData();
        $userData->setLogin($userLoginRequest->getLogin());
        $userData->setName($userLoginRequest->getName());
        $userData->setEmail($userLoginRequest->getEmail());
        $userData->setIsLdap($userLoginRequest->getisLdap());
        $userData->setPass(Hash::hashKey($userLoginRequest->getPassword()));

        return $this->create($userData);
    }

    /**
     * Creates an item
     *
     * @param UserData $itemData
     * @return mixed
     * @throws SPException
     */
    public function create($itemData)
    {
        return $this->userRepository->create($itemData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return array
     */
    public function search(ItemSearchData $SearchData)
    {
        return $this->userRepository->search($SearchData);
    }

    /**
     * Updates an item
     *
     * @param UserData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        return $this->userRepository->update($itemData);
    }

    /**
     * Updates an user's pass
     *
     * @param UserData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updatePass(UserData $itemData)
    {
        $passRequest = new UpdatePassRequest(Hash::hashKey($itemData->getPass()));
        $passRequest->setIsChangePass(0);
        $passRequest->setIsChangedPass(1);

        return $this->userRepository->updatePassById($itemData->getId(), $passRequest);
    }

    /**
     * @param UserLoginRequest $userLoginRequest
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     * @return array
     */
    public function getAllBasic()
    {
        return $this->userRepository->getBasicInfo();
    }
}