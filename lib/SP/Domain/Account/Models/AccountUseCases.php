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

namespace SP\Domain\Account\Models;

use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;

/**
 * Trait AccountUseCases
 */
trait AccountUseCases
{
    public static function create(AccountCreateDto $accountCreateDto): Account
    {
        $account = new Account();
        $account->passDate = time();
        self::buildCommon($accountCreateDto, $account);

        return $account;
    }

    /**
     * @param AccountCreateDto $accountDto
     * @param Account $account
     *
     * @return void
     */
    private static function buildCommon(AccountDto $accountDto, Account $account): void
    {
        $account->userId = $accountDto->getUserId();
        $account->userGroupId = $accountDto->getUserGroupId();
        $account->userGroupId = $accountDto->getUserGroupId();
        $account->name = $accountDto->getName();
        $account->clientId = $accountDto->getClientId();
        $account->login = $accountDto->getLogin();
        $account->url = $accountDto->getUrl();
        $account->notes = $accountDto->getNotes();
        $account->isPrivate = (int)$accountDto->getIsPrivate();
        $account->isPrivateGroup = (int)$accountDto->getIsPrivateGroup();
        $account->passDateChange = $accountDto->getPassDateChange();
        $account->parentId = $accountDto->getParentId();
        $account->otherUserEdit = (int)$accountDto->getOtherUserEdit();
        $account->otherUserGroupEdit = (int)$accountDto->getOtherUserGroupEdit();
    }

    public static function update(AccountUpdateDto $accountUpdateDto): Account
    {
        $account = new Account();
        self::buildCommon($accountUpdateDto, $account);

        return $account;
    }

    public static function updatePassword(AccountUpdateDto $accountUpdateDto): Account
    {
        $account = new Account();
        $account->pass = $accountUpdateDto->getPass();
        $account->key = $accountUpdateDto->getKey();
        $account->passDate = time();
        $account->userEditId = $accountUpdateDto->getUserEditId();
        $account->passDateChange = $accountUpdateDto->getPassDateChange();

        return $account;
    }

    public static function restoreRemoved(AccountHistoryDto $accountHistoryDto, int $userEditId): Account
    {
        $account = new Account();
        $account->pass = $accountHistoryDto->getPass();
        $account->key = $accountHistoryDto->getKey();
        $account->userEditId = $userEditId;
        $account->passDate = $accountHistoryDto->getPassDate();
        $account->dateAdd = $accountHistoryDto->getDateAdd();
        $account->dateEdit = $accountHistoryDto->getDateEdit();
        $account->countView = $accountHistoryDto->getCountView();
        $account->countDecrypt = $accountHistoryDto->getCountDecrypt();

        self::buildCommon($accountHistoryDto, $account);

        return $account;
    }

    public static function restoreModified(AccountHistoryDto $accountHistoryDto, int $userEditId): Account
    {
        $account = new Account();
        $account->pass = $accountHistoryDto->getPass();
        $account->key = $accountHistoryDto->getKey();
        $account->userEditId = $userEditId;

        self::buildCommon($accountHistoryDto, $account);

        return $account;
    }
}
