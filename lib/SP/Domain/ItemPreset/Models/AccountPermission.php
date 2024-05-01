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

namespace SP\Domain\ItemPreset\Models;

/**
 * Class AccountPermission
 *
 * TODO: serde using JSON
 */
readonly class AccountPermission
{
    public function __construct(
        private array $usersView,
        private array $usersEdit,
        private array $userGroupsView,
        private array $userGroupsEdit
    ) {
    }

    public function getUsersView(): array
    {
        return $this->usersView;
    }

    public function getUsersEdit(): array
    {
        return $this->usersEdit;
    }

    public function getUserGroupsView(): array
    {
        return $this->userGroupsView;
    }

    public function getUserGroupsEdit(): array
    {
        return $this->userGroupsEdit;
    }

    public function hasItems(): bool
    {
        return count($this->usersView) > 0
               || count($this->usersEdit) > 0
               || count($this->userGroupsView) > 0
               || count($this->userGroupsEdit) > 0;
    }
}
