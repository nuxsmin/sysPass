<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Providers\Auth\Database;

use Exception;
use SP\Core\Crypt\Hash;
use SP\DataModel\UserLoginData;
use SP\Domain\User\Ports\UserPassServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserLoginResponse;
use SP\Domain\User\Services\UserService;

use function SP\processException;

/**
 * Class Database
 *
 * Autentificación basada en base de datos
 *
 * @package SP\Providers\Auth\Database
 */
final class DatabaseAuth implements DatabaseAuthInterface
{
    public function __construct(
        private readonly UserServiceInterface     $userService,
        private readonly UserPassServiceInterface $userPassService
    ) {
    }


    /**
     * Autentificar al usuario
     *
     * @param UserLoginData $userLoginData Datos del usuario
     *
     * @return DatabaseAuthData
     */
    public function authenticate(UserLoginData $userLoginData): DatabaseAuthData
    {
        $authData = new DatabaseAuthData($this->isAuthGranted());

        return $this->authUser($userLoginData) ? $authData->success() : $authData->fail();
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return bool
     */
    public function isAuthGranted(): bool
    {
        return true;
    }

    protected function authUser(UserLoginData $userLoginData): bool
    {
        try {
            $userLoginResponse =
                UserService::mapUserLoginResponse($this->userService->getByLogin($userLoginData->getLoginUser()));

            $userLoginData->setUserLoginResponse($userLoginResponse);

            if ($userLoginResponse->getIsMigrate()
                && $this->checkMigrateUser($userLoginResponse, $userLoginData)
            ) {
                $this->userPassService->migrateUserPassById(
                    $userLoginResponse->getId(),
                    $userLoginData->getLoginPass()
                );

                return true;
            }

            return Hash::checkHashKey($userLoginData->getLoginPass(), $userLoginResponse->getPass());
        } catch (Exception $e) {
            processException($e);
        }

        return false;
    }

    protected function checkMigrateUser(UserLoginResponse $userLoginResponse, UserLoginData $userLoginData): bool
    {
        $passHashSha = sha1($userLoginResponse->getHashSalt() . $userLoginData->getLoginPass());

        return ($userLoginResponse->getPass() === $passHashSha
                || $userLoginResponse->getPass() === md5($userLoginData->getLoginPass())
                || hash_equals(
                    $userLoginResponse->getPass(),
                    crypt($userLoginData->getLoginPass(), $userLoginResponse->getHashSalt())
                )
                || Hash::checkHashKey($userLoginData->getLoginPass(), $userLoginResponse->getPass()));
    }
}
