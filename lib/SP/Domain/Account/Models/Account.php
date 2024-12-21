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

namespace SP\Domain\Account\Models;

use SP\Domain\Common\Models\Model;

/**
 * Class Account
 */
final class Account extends Model
{
    use AccountUseCases;

    public const TABLE = 'Account';

    protected ?int    $id                 = null;
    protected ?int    $userId             = null;
    protected ?int    $userGroupId        = null;
    protected ?int    $userEditId         = null;
    protected ?string $name               = null;
    protected ?int    $clientId           = null;
    protected ?int    $categoryId         = null;
    protected ?string $login              = null;
    protected ?string $url                = null;
    protected ?string $pass               = null;
    protected ?string $key                = null;
    protected ?string $notes              = null;
    protected ?string $dateAdd  = null;
    protected ?string $dateEdit = null;
    protected ?int    $countView          = null;
    protected ?int    $countDecrypt       = null;
    protected ?int    $isPrivate          = null;
    protected ?int    $isPrivateGroup     = null;
    protected ?int    $passDate           = null;
    protected ?int    $passDateChange     = null;
    protected ?int    $parentId           = null;
    protected ?int    $otherUserGroupEdit = null;
    protected ?int    $otherUserEdit      = null;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserGroupId(): ?int
    {
        return $this->userGroupId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getIsPrivate(): ?int
    {
        return $this->isPrivate;
    }

    public function getIsPrivateGroup(): ?int
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

    public function getOtherUserEdit(): ?int
    {
        return $this->otherUserEdit;
    }

    public function getOtherUserGroupEdit(): ?int
    {
        return $this->otherUserGroupEdit;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserEditId(): ?int
    {
        return $this->userEditId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getDateAdd(): ?string
    {
        return $this->dateAdd;
    }

    public function getDateEdit(): ?string
    {
        return $this->dateEdit;
    }

    public function getCountView(): ?int
    {
        return $this->countView;
    }

    public function getCountDecrypt(): ?int
    {
        return $this->countDecrypt;
    }

    public function getPassDate(): ?int
    {
        return $this->passDate;
    }
}
