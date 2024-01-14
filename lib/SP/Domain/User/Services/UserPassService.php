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

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Application;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\DataModel\UserLoginData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Config\Services\ConfigFile;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserPassServiceInterface;
use SP\Domain\User\Ports\UserRepositoryInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\User\Repositories\UserRepository;

/**
 * Class UserPassService
 *
 * @package SP\Domain\User\Services
 */
final class UserPassService extends Service implements UserPassServiceInterface
{
    // La clave maestra incorrecta
    public const MPASS_WRONG = 0;
    // La clave maestra correcta
    public const MPASS_OK = 1;
    // La clave maestra no está guardada
    public const MPASS_NOTSET = 2;
    // La clave maestra ha cambiado
    public const MPASS_CHANGED = 3;
    // Comprobar la clave maestra con la clave del usuario anterior
    public const MPASS_CHECKOLD = 4;

    private ConfigDataInterface    $configData;
    private UserRepository $userRepository;
    private ConfigService  $configService;

    public function __construct(
        Application $application,
        UserRepositoryInterface $userRepository,
        ConfigService $configService
    ) {
        parent::__construct($application);

        $this->userRepository = $userRepository;
        $this->configService = $configService;

        $this->configData = $this->config->getConfigData();
    }

    /**
     * Actualizar la clave maestra con la clave anterior del usuario
     *
     * @throws SPException
     * @throws CryptoException
     */
    public function updateMasterPassFromOldPass(
        string $oldUserPass,
        UserLoginData $userLoginData
    ): UserPassResponse {
        $response = $this->loadUserMPass($userLoginData, $oldUserPass);

        if ($response->getStatus() === self::MPASS_OK) {
            return $this->updateMasterPassOnLogin($response->getClearMasterPass(), $userLoginData);
        }

        return new UserPassResponse(self::MPASS_WRONG);
    }

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @throws SPException
     */
    public function loadUserMPass(
        UserLoginData $userLoginData,
        ?string $userPass = null
    ): UserPassResponse {
        $userLoginResponse = $userLoginData->getUserLoginResponse();

        $configHashMPass = $this->configService->getByParam('masterPwd');

        if (empty($configHashMPass)
            || $userLoginResponse === null
            || empty($userLoginResponse->getMPass())
            || empty($userLoginResponse->getMKey())
        ) {
            return new UserPassResponse(self::MPASS_NOTSET);
        }

        if ($userLoginResponse->getLastUpdateMPass() < $this->configService->getByParam('lastupdatempass')) {
            return new UserPassResponse(self::MPASS_CHANGED);
        }

        if ($userPass === null && $userLoginResponse->getIsChangedPass()) {
            return new UserPassResponse(self::MPASS_CHECKOLD);
        }

        try {
            $key = $this->makeKeyForUser(
                $userLoginData->getLoginUser(),
                $userPass ?: $userLoginData->getLoginPass()
            );

            $clearMPass = Crypt::decrypt(
                $userLoginResponse->getMPass(),
                $userLoginResponse->getMKey(),
                $key
            );

            // Comprobamos el hash de la clave del usuario con la guardada
            if (Hash::checkHashKey($clearMPass, $configHashMPass)) {
                $this->setMasterKeyInContext($clearMPass);

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
     * @return string con la clave de cifrado
     */
    public function makeKeyForUser(string $userLogin, string $userPass): string
    {
        // Use always the most recent config data
        if (ConfigFile::getTimeUpdated() > $this->configData->getConfigDate()) {
            return trim($userPass.$userLogin.$this->config->getConfigData()->getPasswordSalt());
        }

        return trim(
            $userPass.
            $userLogin.
            $this->configData->getPasswordSalt()
        );
    }

    /**
     * Actualizar la clave maestra del usuario al realizar login
     *
     * @throws SPException
     * @throws CryptoException
     * @throws SPException
     */
    public function updateMasterPassOnLogin(string $userMPass, UserLoginData $userLoginData): UserPassResponse
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
            $response = $this->createMasterPass(
                $userMPass,
                $userLoginData->getLoginUser(),
                $userLoginData->getLoginPass()
            );

            $this->userRepository->updateMasterPassById(
                $userData->getId(),
                $response->getCryptMasterPass(),
                $response->getCryptSecuredKey()
            );

            // Tells that the master password has been updated
            $this->context->setTrasientKey('mpass_updated', true);

            $this->setMasterKeyInContext($userMPass);

            return $response;
        }

        return new UserPassResponse(self::MPASS_WRONG);
    }

    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @throws CryptoException
     * @throws SPException
     */
    public function createMasterPass(string $masterPass, string $userLogin, string $userPass): UserPassResponse
    {
        $key = $this->makeKeyForUser($userLogin, $userPass);

        $securedKey = Crypt::makeSecuredKey($key);
        $cryptMPass = Crypt::encrypt($masterPass, $securedKey, $key);

        if (strlen($securedKey) > 1000 || strlen($cryptMPass) > 1000) {
            throw new SPException(
                __u('Internal error'),
                SPException::ERROR,
                '',
                Service::STATUS_INTERNAL_ERROR
            );
        }

        $response = new UserPassResponse(self::MPASS_OK, $masterPass);
        $response->setCryptMasterPass($cryptMPass);
        $response->setCryptSecuredKey($securedKey);

        return $response;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function migrateUserPassById(int $id, string $userPass): void
    {
        $updatePassById = $this->userRepository->updatePassById(
            $id,
            new UpdatePassRequest(Hash::hashKey($userPass))
        );

        if ($updatePassById === 0) {
            throw new NoSuchItemException(__u('User does not exist'));
        }
    }
}
