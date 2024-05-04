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

/**
 * Class AccountRequest
 */
final class AccountRequest
{
    public ?int    $id                 = null;
    public ?string $name               = null;
    public ?int    $clientId           = null;
    public ?int    $categoryId         = null;
    public ?string $login              = null;
    public ?string $url                = null;
    public ?string $notes              = null;
    public ?int    $userId             = null;
    public ?int    $userGroupId        = null;
    public ?int    $userEditId         = null;
    public ?int    $otherUserEdit      = null;
    public ?int    $otherUserGroupEdit = null;
    public ?string $pass               = null;
    public ?string $key                = null;
    public ?int    $isPrivate          = null;
    public ?int    $isPrivateGroup     = null;
    public ?int    $passDateChange     = null;
    public ?int    $parentId           = null;
    public ?array  $usersView          = null;
    public ?array  $usersEdit          = null;
    public ?array  $userGroupsView     = null;
    public ?array  $userGroupsEdit     = null;
    public ?array  $tags               = null;
    public ?bool   $changeOwner        = false;
    public ?bool   $changeUserGroup    = false;
    public ?bool   $changePermissions  = false;
    public ?bool   $updateTags         = false;
}
