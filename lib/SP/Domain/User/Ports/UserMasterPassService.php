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

use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Dtos\UserMasterPassDto;

/**
 * Class UserPassService
 *
 * @package SP\Domain\User\Services
 */
interface UserMasterPassService
{
    /**
     * Update the current user's master password with the previous user's login password
     *
     * @throws ServiceException
     */
    public function updateFromOldPass(
        string       $oldUserPass,
        UserLoginDto $userLoginDto,
        UserDataDto  $userDataDto
    ): UserMasterPassDto;

    /**
     * Load the user's master password
     *
     * @throws ServiceException
     */
    public function load(
        UserLoginDto $userLoginDto,
        UserDataDto  $userDataDto,
        ?string      $userPass = null
    ): UserMasterPassDto;

    /**
     * Update the user's master pass on log in.
     * It requires the user's login data to build a secure key to store the master password
     *
     * @param string $userMasterPass
     * @param UserLoginDto $userLoginDto
     * @param int $userId
     * @return UserMasterPassDto
     * @throws ServiceException
     */
    public function updateOnLogin(string $userMasterPass, UserLoginDto $userLoginDto, int $userId): UserMasterPassDto;

    /**
     * Update the user's master password in the database
     *
     * @param string $masterPass
     * @param string $userLogin
     * @param string $userPass
     * @return UserMasterPassDto
     * @throws ServiceException
     */
    public function create(string $masterPass, string $userLogin, string $userPass): UserMasterPassDto;
}
