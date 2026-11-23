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

namespace SP\Domain\User\Dtos;

use SP\Domain\Common\Attributes\DtoTransformation;
use SP\Domain\Common\Attributes\ModelBounded;
use SP\Domain\Common\Dtos\Dto;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserPreferences;

/**
 * Class UserDto
 */
#[ModelBounded(User::class)]
final class UserDto extends Dto
{
    public function __construct(
        public readonly ?int             $id = null,
        public readonly ?int             $lastUpdateMPass = null,
        public readonly ?int             $userGroupId = null,
        public readonly ?int             $userProfileId = null,
        public readonly ?int             $loginCount = null,
        public readonly ?string          $pass = null,
        public readonly ?string          $hashSalt = null,
        public readonly ?string          $mPass = null,
        public readonly ?string          $mKey = null,
        public readonly ?string          $login = null,
        public readonly ?string          $ssoLogin = null,
        public readonly ?string          $name = null,
        public readonly ?string          $email = null,
        public readonly ?string          $notes = null,
        public readonly ?string          $lastLogin = null,
        public readonly ?string          $lastUpdate = null,
        public readonly ?string          $userGroupName = null,
        public readonly ?bool            $isAdminApp = null,
        public readonly ?bool            $isAdminAcc = null,
        public readonly ?bool            $isDisabled = null,
        public readonly ?bool            $isChangePass = null,
        public readonly ?bool            $isChangedPass = null,
        public readonly ?bool            $isLdap = null,
        public readonly ?bool            $isMigrate = null,
        public readonly ?UserPreferences $preferences = null
    ) {
    }

    /**
     * @throws SPException
     */
    #[DtoTransformation('preferences')]
    private static function transformPreferences(User $user): UserPreferences
    {
        return $user->hydrate(UserPreferences::class);
    }
}
