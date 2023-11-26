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

namespace SP\Domain\Account\Ports;

use SP\DataModel\ProfileData;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Services\UserLoginResponse;

/**
 * Class AccountAclService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountAclServiceInterface
{
    /**
     * Sets grants which don't need the account's data
     *
     * @param  UserLoginResponse  $userData
     * @param  ProfileData  $profileData
     *
     * @return bool
     */
    public static function getShowPermission(UserLoginResponse $userData, ProfileData $profileData): bool;

    /**
     * Obtener la ACL de una cuenta
     *
     * @param  int  $actionId
     * @param  AccountAclDto  $accountAclDto
     * @param  bool  $isHistory
     *
     * @return AccountAcl
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAcl(int $actionId, AccountAclDto $accountAclDto, bool $isHistory = false): AccountAcl;

    /**
     * Resturns an stored ACL
     *
     * @param  int  $accountId
     * @param  int  $actionId
     *
     * @return AccountAcl|null
     */
    public function getAclFromCache(int $accountId, int $actionId): ?AccountAcl;
}
