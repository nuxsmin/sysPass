<?php
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

declare(strict_types=1);

namespace SP\Domain\Account\Dtos;

use SP\Domain\Common\Dtos\Dto;

/**
 * Class AccountViewDto
 */
class AccountViewDto extends Dto
{
    public function __construct(
        protected int     $id,
        protected string  $name,
        protected string  $login,
        protected int     $clientId,
        protected int     $categoryId,
        protected string  $pass,
        protected int     $userId,
        protected string  $userName,
        protected string  $key,
        protected string  $url,
        protected string  $notes,
        protected int     $userEditId,
        protected string  $userEditName,
        protected string  $userEditLogin,
        protected bool    $isPrivate,
        protected bool    $isPrivateGroup,
        protected int     $userGroupId,
        protected string  $userGroupName,
        protected bool    $otherUserEdit,
        protected bool    $otherUserGroupEdit,
        protected int     $countView,
        protected int     $countDecrypt,
        protected string  $dateAdd,
        protected ?string $dateEdit = null,
        protected ?int    $passDate = null,
        protected ?int    $passDateChange = null,
        protected ?int    $parentId = null,
        protected ?array  $usersView = null,
        protected ?array  $usersEdit = null,
        protected ?array  $userGroupsView = null,
        protected ?array  $userGroupsEdit = null,
        protected ?array  $tags = null,
        protected ?string $categoryName = null,
        protected ?string $clientName = null,
    ) {
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function getPassDate(): ?int
    {
        return $this->passDate;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getPass(): string
    {
        return $this->pass;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getUserEditId(): int
    {
        return $this->userEditId;
    }

    public function getUserEditName(): string
    {
        return $this->userEditName;
    }

    public function getUserEditLogin(): string
    {
        return $this->userEditLogin;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function isPrivateGroup(): bool
    {
        return $this->isPrivateGroup;
    }

    public function getUserGroupId(): int
    {
        return $this->userGroupId;
    }

    public function getUserGroupName(): string
    {
        return $this->userGroupName;
    }

    public function isOtherUserEdit(): bool
    {
        return $this->otherUserEdit;
    }

    public function isOtherUserGroupEdit(): bool
    {
        return $this->otherUserGroupEdit;
    }

    public function getPassDateChange(): ?int
    {
        return $this->passDateChange;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getUsersView(): ?array
    {
        return $this->usersView;
    }

    public function getUsersEdit(): ?array
    {
        return $this->usersEdit;
    }

    public function getUserGroupsView(): ?array
    {
        return $this->userGroupsView;
    }

    public function getUserGroupsEdit(): ?array
    {
        return $this->userGroupsEdit;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getCountView(): int
    {
        return $this->countView;
    }

    public function getCountDecrypt(): int
    {
        return $this->countDecrypt;
    }

    public function getDateAdd(): string
    {
        return $this->dateAdd;
    }

    public function getDateEdit(): ?string
    {
        return $this->dateEdit;
    }
}
