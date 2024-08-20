<?php

declare(strict_types=1);
/**
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
        ?int                    $id = null,
        ?int                    $clientId = null,
        ?int                    $categoryId = null,
        ?int                    $userId = null,
        ?int                    $userGroupId = null,
        ?int                    $userEditId = null,
        ?int                    $parentId = null,
        ?int                    $countView = null,
        ?int                    $countDecrypt = null,
        ?int                    $passDateChange = null,
        ?string                 $name = null,
        ?string                 $login = null,
        ?string                 $pass = null,
        ?string                 $key = null,
        ?string                 $url = null,
        ?string                 $notes = null,
        ?bool                   $isPrivate = null,
        ?bool                   $isPrivateGroup = null,
        ?bool                   $otherUserEdit = null,
        ?bool                   $otherUserGroupEdit = null,
        ?array                  $usersView = null,
        ?array                  $usersEdit = null,
        ?array                  $otherUserGroupsView = null,
        ?array                  $otherUserGroupsEdit = null,
        ?array                  $tags = null,
        ?array                  $userGroupsView = null,
        ?array                  $userGroupsEdit = null,
        public readonly ?int    $accountId = null,
        public readonly ?int    $isDelete = null,
        public readonly ?int    $isModify = null,
        public readonly ?int    $passDate = null,
        public readonly ?string $dateAdd = null,
        public readonly ?string $dateEdit = null,
    ) {
        parent::__construct(
            $id,
            $clientId,
            $categoryId,
            $userId,
            $userGroupId,
            $userEditId,
            $parentId,
            $countView,
            $countDecrypt,
            $passDateChange,
            $name,
            $login,
            $pass,
            $key,
            $url,
            $notes,
            $isPrivate,
            $isPrivateGroup,
            $otherUserEdit,
            $otherUserGroupEdit,
            $usersView,
            $usersEdit,
            $otherUserGroupsView,
            $otherUserGroupsEdit,
            $tags,
            $userGroupsView,
            $userGroupsEdit
        );
    }
}
