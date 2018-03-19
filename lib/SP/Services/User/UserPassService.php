<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

use Defuse\Crypto\Exception\CryptoException;
use SP\Config\ConfigData;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\SPException;
use SP\Core\Upgrade\User as UpgradeUser;
use SP\DataModel\UserLoginData;
use SP\Repositories\User\UserRepository;
use SP\Services\Config\ConfigService;
use SP\Services\Service;

/**
 * Class UserPassService
 *
 * @package SP\Services\User
 */
class UserPassService extends Service
{
    // La clave maestra incorrecta
    const MPASS_WRONG = 0;
    // La clave maestra correcta
    const MPASS_OK = 1;
    // La clave maestra no está guardada
    const MPASS_NOTSET = 2;
    // La clave maestra ha cambiado
    const MPASS_CHANGED = 3;
    // Comprobar la clave maestra con la clave del usuario anterior
    const MPASS_CHECKOLD = 4;

    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * Actualizar la clave maestra con la clave anterior del usuario
     *
     * @param string        $oldUserPass
     * @param UserLoginData $userLoginData $UserData
     * @return UserPassResponse
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function updateMasterPassFromOldPass($oldUserPass, UserLoginData $userLoginData)
    {
        $response = $this->loadUserMPass($userLoginData, $oldUserPass);

        if ($response->getStatus() === self::MPASS_OK) {
            return $this->updateMasterPassOnLogin($response->getClearMasterPass(), $userLoginData);
        }

        return new UserPassResponse(self::MPASS_WRONG);
    }

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @param UserLoginData $userLoginData
     * @param string        $key Clave de cifrado
     * @return UserPassResponse
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function loadUserMPass(UserLoginData $userLoginData, $key = null)
    {
        $userLoginResponse = $userLoginData->getUserLoginResponse();

        $configHashMPass = $this->configService->getByParam('masterPwd');

        if (empty($configHashMPass)
            || empty($userLoginResponse->getMPass())
            || empty($userLoginResponse->getMKey())
        ) {
            return new UserPassResponse(self::MPASS_NOTSET);
        }

        if ($userLoginResponse->getLastUpdateMPass() < $this->configService->getByParam('lastupdatempass')) {
            return new UserPassResponse(self::MPASS_CHANGED);
        }

        if ($userLoginResponse->getIsMigrate() === 1) {
            // FIXME
            return UpgradeUser::upgradeMasterKey($userLoginData, $this) ? new UserPassResponse(self::MPASS_OK) : new UserPassResponse(self::MPASS_WRONG);
        }

        if ($key === null && $userLoginResponse->getIsChangedPass() === 1) {
            return new UserPassResponse(self::MPASS_CHECKOLD);
        }

        try {
            $key = self::makeKeyForUser($userLoginData->getLoginUser(), $key ?: $userLoginData->getLoginPass(), $this->configData);
            $securedKey = Crypt::unlockSecuredKey($userLoginResponse->getMKey(), $key);
            $clearMPass = Crypt::decrypt($userLoginResponse->getMPass(), $securedKey, $key);

            // Comprobamos el hash de la clave del usuario con la guardada
            if (Hash::checkHashKey($clearMPass, $configHashMPass)) {
                CryptSession::saveSessionKey($clearMPass, $this->context);

                $response = new UserPassResponse(self::MPASS_OK, $clearMPass);
                $response->setCryptMasterPass($userLoginResponse->getMPass());
                $response->setCryptSecuredKey($userLoginResponse->getMKey());

                return $response;
            }
        } catch (CryptoException $e) {
            return new UserPassResponse(self::MPASS_CHECKOLD);
        }

        return new UserPassResponse(self::MPASS_WRONG);
    }

    /**
     * Obtener una clave de cifrado basada en la clave del usuario y un salt.
     *
     * @param string     $userLogin
     * @param string     $userPass
     * @param ConfigData $configData
     * @return string con la clave de cifrado
     */
    public static function makeKeyForUser($userLogin, $userPass, ConfigData $configData)
    {
        return $userPass . $userLogin . $configData->getPasswordSalt();
    }

    /**
     * Actualizar la clave maestra del usuario al realizar login
     *
     * @param string        $userMPass     con la clave maestra
     * @param UserLoginData $userLoginData $userLoginData
     * @return UserPassResponse
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateMasterPassOnLogin($userMPass, UserLoginData $userLoginData)
    {
        $userData = $userLoginData->getUserLoginResponse();
        $configHashMPass = $this->configService->getByParam('masterPwd');

        if ($configHashMPass === false) {
            return new UserPassResponse(self::MPASS_NOTSET);
        }

        if (null === $configHashMPass) {
            $configHashMPass = Hash::hashKey($userMPass);

            $this->configService->save('masterPwd', $configHashMPass);
        }

        if (Hash::checkHashKey($userMPass, $configHashMPass)) {
            $response = $this->createMasterPass($userMPass, $userLoginData->getLoginUser(), $userLoginData->getLoginPass());

            $this->userRepository->updateMasterPassById($userData->getId(), $response->getCryptMasterPass(), $response->getCryptSecuredKey());

            CryptSession::saveSessionKey($userMPass, $this->context);

            return $response;
        }

        return new UserPassResponse(self::MPASS_WRONG);
    }

    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @param string $masterPass
     * @param string $userLogin
     * @param string $userPass
     * @return UserPassResponse
     * @throws CryptoException
     * @throws SPException
     */
    public function createMasterPass($masterPass, $userLogin, $userPass)
    {
        $key = self::makeKeyForUser($userLogin, $userPass, $this->configData);
        $securedKey = Crypt::makeSecuredKey($key);
        $cryptMPass = Crypt::encrypt($masterPass, $securedKey, $key);

        if (strlen($securedKey) > 1000 || strlen($cryptMPass) > 1000) {
            throw new SPException(
                __u('Error interno'),
                SPException::ERROR,
                '',
                Service::STATUS_INTERNAL_ERROR);
        }

        $response = new UserPassResponse(self::MPASS_OK, $masterPass);
        $response->setCryptMasterPass($cryptMPass);
        $response->setCryptSecuredKey($securedKey);

        return $response;
    }

    /**
     * @param int    $id
     * @param string $userPass
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function migrateUserPassById($id, $userPass)
    {
        return $this->userRepository->updatePassById($id, new UpdatePassRequest(Hash::hashKey($userPass)));
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configData = $this->config->getConfigData();
        $this->userRepository = $this->dic->get(UserRepository::class);
        $this->configService = $this->dic->get(ConfigService::class);;
    }
}