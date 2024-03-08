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

namespace SP\Domain\Import\Dtos;

/**
 * Class LdapImportParams
 */
final class LdapImportParamsDto
{
    public function __construct(
        private readonly ?int    $defaultUserGroup = null,
        private readonly ?int    $defaultUserProfile = null,
        private readonly ?string $loginAttribute = null,
        private readonly ?string $userNameAttribute = null,
        private readonly ?string $userGroupNameAttribute = null,
        private readonly ?string $filter = null
    ) {
    }

    public function getDefaultUserGroup(): ?int
    {
        return $this->defaultUserGroup;
    }

    public function getDefaultUserProfile(): ?int
    {
        return $this->defaultUserProfile;
    }

    public function getLoginAttribute(): ?string
    {
        return $this->loginAttribute;
    }

    public function getUserNameAttribute(): ?string
    {
        return $this->userNameAttribute;
    }

    public function getUserGroupNameAttribute(): ?string
    {
        return $this->userGroupNameAttribute;
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }
}
