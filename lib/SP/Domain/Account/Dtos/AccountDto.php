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

use SP\Domain\Account\Models\Account;
use SP\Domain\Common\Dtos\Dto;

/**
 * Class AccountDto
 */
abstract class AccountDto extends Dto
{
    public function __construct(
        protected ?string $name = null,
        protected ?string $login = null,
        protected ?int   $clientId = null,
        protected ?int   $categoryId = null,
        protected ?string $pass = null,
        protected ?int   $userId = null,
        protected ?string $key = null,
        protected ?string $url = null,
        protected ?string $notes = null,
        protected ?int   $userEditId = null,
        protected ?bool  $isPrivate = null,
        protected ?bool  $isPrivateGroup = null,
        protected ?int   $passDateChange = null,
        protected ?int   $parentId = null,
        protected ?int   $userGroupId = null,
        protected ?bool  $otherUserEdit = null,
        protected ?bool  $otherUserGroupEdit = null,
        protected ?array $usersView = null,
        protected ?array $usersEdit = null,
        protected ?array $userGroupsView = null,
        protected ?array $userGroupsEdit = null,
        protected ?array $tags = null
    ) {
    }

    public static function fromAccount(Account $account): static
    {
        return new static(
            name:               $account->getName(),
            login:              $account->getLogin(),
            clientId:           $account->getClientId(),
            categoryId:         $account->getCategoryId(),
            pass:               $account->getPass(),
            userId:             $account->getUserId(),
            key:                $account->getKey(),
            url:                $account->getUrl(),
            notes:              $account->getNotes(),
            userEditId:         $account->getUserEditId(),
            isPrivate:          (bool)$account->getIsPrivate(),
            isPrivateGroup:     (bool)$account->getIsPrivateGroup(),
            passDateChange:     $account->getPassDateChange(),
            parentId:           $account->getParentId(),
            userGroupId:        $account->getUserGroupId(),
            otherUserEdit:      (bool)$account->getOtherUserEdit(),
            otherUserGroupEdit: (bool)$account->getOtherUserGroupEdit(),
        );
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getUserEditId(): ?int
    {
        return $this->userEditId;
    }

    public function getIsPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function getIsPrivateGroup(): ?bool
    {
        return $this->isPrivateGroup;
    }

    public function getPassDateChange(): ?int
    {
        return $this->passDateChange;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getUserGroupId(): ?int
    {
        return $this->userGroupId;
    }

    public function getOtherUserEdit(): ?bool
    {
        return $this->otherUserEdit;
    }

    public function getOtherUserGroupEdit(): ?bool
    {
        return $this->otherUserGroupEdit;
    }

    public function withUserId(int $userId): static
    {
        $self = clone $this;
        $self->userId = $userId;

        return $self;
    }

    public function withUserGroupId(int $userGroupId): static
    {
        $self = clone $this;
        $self->userGroupId = $userGroupId;

        return $self;
    }

    public function withPrivate(bool $isPrivate): static
    {
        $self = clone $this;
        $self->isPrivate = $isPrivate;

        return $self;
    }

    public function withPrivateGroup(bool $isPrivateGroup): static
    {
        $self = clone $this;
        $self->isPrivateGroup = $isPrivateGroup;

        return $self;
    }

    public function withEncryptedPassword(EncryptedPassword $encryptedPassword): static
    {
        $self = clone $this;
        $self->pass = $encryptedPassword->getPass();
        $self->key = $encryptedPassword->getKey();

        return $self;
    }

    public function withUsersView(array $users): static
    {
        $self = clone $this;
        $self->usersView = $users;

        return $self;
    }

    public function withUsersEdit(array $users): static
    {
        $self = clone $this;
        $self->usersEdit = $users;

        return $self;
    }

    public function withUserGroupsView(array $userGroups): static
    {
        $self = clone $this;
        $self->userGroupsView = $userGroups;

        return $self;
    }

    public function withUserGroupsEdit(array $userGroups): static
    {
        $self = clone $this;
        $self->userGroupsEdit = $userGroups;

        return $self;
    }

    public function withTags(array $tags): static
    {
        $self = clone $this;
        $self->tags = $tags;

        return $self;
    }

    public function withPassDateChange(int $passDateChange): static
    {
        $self = clone $this;
        $self->passDateChange = $passDateChange;

        return $self;
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
}
