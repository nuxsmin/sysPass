<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\DataModel\Dto;

use SP\DataModel\AccountHistoryData;
use SP\DataModel\AccountSearchVData;
use SP\DataModel\ItemData;

/**
 * Class AccountAclDto
 *
 * @package SP\DataModel\Dto
 */
final class AccountAclDto
{
    /**
     * @var int
     */
    private $accountId;
    /**
     * @var int
     */
    private $userId;
    /**
     * @var ItemData[]
     */
    private $usersId;
    /**
     * @var int
     */
    private $userGroupId;
    /**
     * @var ItemData[]
     */
    private $userGroupsId;
    /**
     * @var int
     */
    private $dateEdit;

    private function __construct()
    {
    }

    /**
     * @param AccountDetailsResponse $accountDetailsResponse
     *
     * @return AccountAclDto
     */
    public static function makeFromAccount(AccountDetailsResponse $accountDetailsResponse)
    {
        $dto = new self();
        $dto->accountId = $accountDetailsResponse->getId();
        $dto->userId = $accountDetailsResponse->getAccountVData()->getUserId();
        $dto->usersId = $accountDetailsResponse->getUsers();
        $dto->userGroupId = $accountDetailsResponse->getAccountVData()->getUserGroupId();
        $dto->userGroupsId = $accountDetailsResponse->getUserGroups();
        $dto->dateEdit = strtotime($accountDetailsResponse->getAccountVData()->getDateEdit());

        return $dto;
    }

    /**
     * @param AccountHistoryData $accountHistoryData
     *
     * @param array              $users
     * @param array              $userGroups
     *
     * @return AccountAclDto
     */
    public static function makeFromAccountHistory(AccountHistoryData $accountHistoryData, array $users, array $userGroups)
    {
        $dto = new self();
        $dto->accountId = $accountHistoryData->getId();
        $dto->userId = $accountHistoryData->getUserId();
        $dto->usersId = $users;
        $dto->userGroupId = $accountHistoryData->getUserGroupId();
        $dto->userGroupsId = $userGroups;
        $dto->dateEdit = strtotime($accountHistoryData->getDateEdit());

        return $dto;
    }

    /**
     * @param AccountSearchVData $accountSearchVData
     *
     * @param array              $users
     * @param array              $userGroups
     *
     * @return AccountAclDto
     */
    public static function makeFromAccountSearch(AccountSearchVData $accountSearchVData, array $users, array $userGroups)
    {
        $dto = new self();
        $dto->accountId = $accountSearchVData->getId();
        $dto->userId = $accountSearchVData->getUserId();
        $dto->usersId = $users;
        $dto->userGroupId = $accountSearchVData->getUserGroupId();
        $dto->userGroupsId = $userGroups;
        $dto->dateEdit = strtotime($accountSearchVData->getDateEdit());

        return $dto;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return ItemData[]
     */
    public function getUsersId()
    {
        return $this->usersId;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->userGroupId;
    }

    /**
     * @return ItemData[]
     */
    public function getUserGroupsId()
    {
        return $this->userGroupsId;
    }

    /**
     * @return int
     */
    public function getDateEdit()
    {
        return $this->dateEdit;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }
}