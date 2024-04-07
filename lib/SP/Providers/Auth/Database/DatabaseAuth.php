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

namespace SP\Providers\Auth\Database;

use Exception;
use SP\Core\Crypt\Hash;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserPassService;

use function SP\processException;

/**
 * Class DatabaseAuth
 */
final readonly class DatabaseAuth implements DatabaseAuthService
{
    public function __construct(
        private UserServiceInterface $userService,
        private UserPassService      $userPassService
    ) {
    }

    /**
     * Authenticate using user's data
     *
     * @param UserLoginDto $userLoginData
     * @return DatabaseAuthData
     */
    public function authenticate(UserLoginDto $userLoginData): DatabaseAuthData
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

    private function authUser(UserLoginDto $userLoginData): bool
    {
        try {
            $userLoginResponse = new UserDataDto($this->userService->getByLogin($userLoginData->getLoginUser()));

            $userLoginData->setUserDataDto($userLoginResponse);

            if ($userLoginResponse->getIsMigrate() && $this->checkMigrateUser($userLoginResponse, $userLoginData)) {
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

    private function checkMigrateUser(UserDataDto $userLoginResponse, UserLoginDto $userLoginData): bool
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
