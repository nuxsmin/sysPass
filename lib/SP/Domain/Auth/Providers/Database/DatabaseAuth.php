<?php
declare(strict_types=1);
/**
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

namespace SP\Domain\Auth\Providers\Database;

use Exception;
use SP\Core\Crypt\Hash;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserPassService;
use SP\Domain\User\Ports\UserService;

use function SP\processException;

/**
 * Class DatabaseAuth
 */
final readonly class DatabaseAuth implements DatabaseAuthService
{
    public function __construct(
        private UserService     $userService,
        private UserPassService $userPassService
    ) {
    }

    /**
     * Authenticate using user's data
     *
     * @param UserLoginDto $userLoginDto
     * @return DatabaseAuthData
     */
    public function authenticate(UserLoginDto $userLoginDto): DatabaseAuthData
    {
        $authUser = $this->authUser($userLoginDto);

        $authData = new DatabaseAuthData($this->isAuthGranted(), $authUser ?: null);

        return $authUser ? $authData->success() : $authData->fail();
    }

    private function authUser(UserLoginDto $userLoginDto): UserDataDto|false
    {
        try {
            $userDataDto = new UserDataDto($this->userService->getByLogin($userLoginDto->getLoginUser()));

            if ($userDataDto->getIsMigrate() && $this->checkMigrateUser($userDataDto, $userLoginDto)) {
                $this->userPassService->migrateUserPassById($userDataDto->getId(), $userLoginDto->getLoginPass());

                return $userDataDto;
            }

            if (Hash::checkHashKey($userLoginDto->getLoginPass(), $userDataDto->getPass())) {
                return $userDataDto;
            }
        } catch (Exception $e) {
            processException($e);
        }

        return false;
    }

    private function checkMigrateUser(UserDataDto $userDataDto, UserLoginDto $userLoginDto): bool
    {
        $passHashSha = sha1($userDataDto->getHashSalt() . $userLoginDto->getLoginPass());

        return ($userDataDto->getPass() === $passHashSha
                || $userDataDto->getPass() === md5($userLoginDto->getLoginPass())
                || hash_equals(
                    $userDataDto->getPass(),
                    crypt($userLoginDto->getLoginPass(), $userDataDto->getHashSalt())
                )
                || Hash::checkHashKey($userLoginDto->getLoginPass(), $userDataDto->getPass()));
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
}
