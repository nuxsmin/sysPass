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

namespace SP\Domain\Account\Dtos;

use SP\Domain\Common\Dtos\Dto;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Class AccountDto
 */
abstract class AccountDto extends Dto
{
    public function __construct(
        public readonly ?int    $id = null,
        public readonly ?int    $clientId = null,
        public readonly ?int    $categoryId = null,
        public readonly ?int    $userId = null,
        public readonly ?int    $userGroupId = null,
        public readonly ?int    $userEditId = null,
        public readonly ?int    $parentId = null,
        public readonly ?int    $countView = null,
        public readonly ?int    $countDecrypt = null,
        public readonly ?int    $passDateChange = null,
        public readonly ?string $name = null,
        public readonly ?string $login = null,
        public readonly ?string $pass = null,
        public readonly ?string $key = null,
        public readonly ?string $url = null,
        public readonly ?string $notes = null,
        public readonly ?bool   $isPrivate = null,
        public readonly ?bool   $isPrivateGroup = null,
        public readonly ?bool   $otherUserEdit = null,
        public readonly ?bool   $otherUserGroupEdit = null,
        public readonly ?array  $usersView = null,
        public readonly ?array  $usersEdit = null,
        public readonly ?array  $otherUserGroupsView = null,
        public readonly ?array  $otherUserGroupsEdit = null,
        public readonly ?array  $tags = null,
        public readonly ?array  $userGroupsView = null,
        public readonly ?array  $userGroupsEdit = null
    ) {
    }

    /**
     * @throws SPException
     */
    public function withUserId(int $id): static
    {
        return $this->mutate(['userId' => $id]);
    }

    /**
     * @throws SPException
     */
    public function withUserGroupId(int $id): static
    {
        return $this->mutate(['userGroupId' => $id]);
    }

    /**
     * @throws SPException
     */
    public function withPrivate(bool $isPrivate): static
    {
        return $this->mutate(['isPrivate' => $isPrivate]);
    }

    /**
     * @throws SPException
     */
    public function withPrivateGroup(bool $isPrivateGroup): static
    {
        return $this->mutate(['isPrivateGroup' => $isPrivateGroup]);
    }

    /**
     * @throws SPException
     */
    public function withEncryptedPassword(EncryptedPassword $encryptedPassword): static
    {
        return $this->mutate(['pass' => $encryptedPassword->getPass(), 'key' => $encryptedPassword->getKey()]);
    }

    /**
     * @throws SPException
     */
    public function withUsersView(array $users): static
    {
        return $this->mutate(['usersView' => $users]);
    }

    /**
     * @throws SPException
     */
    public function withUsersEdit(array $users): static
    {
        return $this->mutate(['usersEdit' => $users]);
    }

    /**
     * @throws SPException
     */
    public function withUserGroupsView(array $userGroups): static
    {
        return $this->mutate(['userGroupsView' => $userGroups]);
    }

    /**
     * @throws SPException
     */
    public function withUserGroupsEdit(array $userGroups): static
    {
        return $this->mutate(['userGroupsEdit' => $userGroups]);
    }

    /**
     * @throws SPException
     */
    public function withTags(array $tags): static
    {
        return $this->mutate(['tags' => $tags]);
    }

    /**
     * @throws SPException
     */
    public function withPassDateChange(int $timestamp): static
    {
        return $this->mutate(['passDateChange' => $timestamp]);
    }
}
