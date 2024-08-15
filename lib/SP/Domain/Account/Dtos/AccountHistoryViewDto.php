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

/**
 * Class AccountHistoryViewDto
 */
final class AccountHistoryViewDto extends AccountViewDto
{
    public function __construct(
        int           $id,
        string        $name,
        string        $login,
        int           $clientId,
        int           $categoryId,
        string        $pass,
        int           $userId,
        string        $userName,
        string        $key,
        string        $url,
        string        $notes,
        int           $userEditId,
        string        $userEditName,
        string        $userEditLogin,
        bool          $isPrivate,
        bool          $isPrivateGroup,
        int           $userGroupId,
        string        $userGroupName,
        bool          $otherUserEdit,
        bool          $otherUserGroupEdit,
        int           $countView,
        int           $countDecrypt,
        string  $dateAdd,
        protected int $accountId,
        protected int $isModify,
        protected int $isDeleted,
        ?string $dateEdit = null,
        ?int          $passDate = null,
        ?int          $passDateChange = null,
        ?int          $parentId = null,
        ?array        $usersView = null,
        ?array        $usersEdit = null,
        ?array        $userGroupsView = null,
        ?array        $userGroupsEdit = null,
        ?array  $tags = null,
        ?string $categoryName = null,
        ?string $clientName = null,
    ) {
        parent::__construct(
            $id,
            $name,
            $login,
            $clientId,
            $categoryId,
            $pass,
            $userId,
            $userName,
            $key,
            $url,
            $notes,
            $userEditId,
            $userEditName,
            $userEditLogin,
            $isPrivate,
            $isPrivateGroup,
            $userGroupId,
            $userGroupName,
            $otherUserEdit,
            $otherUserGroupEdit,
            $countView,
            $countDecrypt,
            $dateAdd,
            $dateEdit,
            $passDate,
            $passDateChange,
            $parentId,
            $usersView,
            $usersEdit,
            $userGroupsView,
            $userGroupsEdit,
            $tags,
            $categoryName,
            $clientName
        );
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getIsModify(): int
    {
        return $this->isModify;
    }

    public function getIsDeleted(): int
    {
        return $this->isDeleted;
    }
}
