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

namespace SP\Domain\Account\Dtos;

/**
 * Class AccountHistoryDto
 */
final class AccountHistoryDto extends AccountDto
{
    public function __construct(
        protected ?int $accountId = null,
        protected ?int $isDelete = null,
        protected ?int $isModify = null,
        protected ?int $dateAdd = null,
        protected ?int $dateEdit = null,
        protected ?int $passDate = null,
        protected ?int $countView = null,
        protected ?int $countDecrypt = null,
        ?string $name = null,
        ?string $login = null,
        ?int    $clientId = null,
        ?int    $categoryId = null,
        ?string $pass = null,
        ?int    $userId = null,
        ?string $key = null,
        ?string $url = null,
        ?string $notes = null,
        ?int    $userEditId = null,
        ?bool   $isPrivate = null,
        ?bool   $isPrivateGroup = null,
        ?int    $passDateChange = null,
        ?int    $parentId = null,
        ?int    $userGroupId = null,
        ?bool   $otherUserEdit = null,
        ?bool   $otherUserGroupEdit = null
    ) {
        parent::__construct(
            $name,
            $login,
            $clientId,
            $categoryId,
            $pass,
            $userId,
            $key,
            $url,
            $notes,
            $userEditId,
            $isPrivate,
            $isPrivateGroup,
            $passDateChange,
            $parentId,
            $userGroupId,
            $otherUserGroupEdit,
            $otherUserEdit
        );
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getIsDelete(): ?int
    {
        return $this->isDelete;
    }

    public function getIsModify(): ?int
    {
        return $this->isModify;
    }

    public function getDateAdd(): ?int
    {
        return $this->dateAdd;
    }

    public function getDateEdit(): ?int
    {
        return $this->dateEdit;
    }

    public function getPassDate(): ?int
    {
        return $this->passDate;
    }

    public function getCountView(): ?int
    {
        return $this->countView;
    }

    public function getCountDecrypt(): ?int
    {
        return $this->countDecrypt;
    }
}
