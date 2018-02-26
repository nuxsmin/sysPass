<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

/**
 * Class AccountAclDto
 *
 * @package SP\DataModel\Dto
 */
class AccountAclDto
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
     * @var int[]
     */
    private $usersId;
    /**
     * @var int
     */
    private $userGroupId;
    /**
     * @var int[]
     */
    private $userGroupsId;
    /**
     * @var int
     */
    private $dateEdit;
    /**
     * @var int
     */
    private $otherUserEdit;
    /**
     * @var int
     */
    private $otherUserGroupEdit;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
    }

    /**
     * @return int[]
     */
    public function getUsersId()
    {
        return $this->usersId;
    }

    /**
     * @param int[] $usersId
     */
    public function setUsersId(array $usersId)
    {
        $this->usersId = $usersId;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->userGroupId;
    }

    /**
     * @param int $userGroupId
     */
    public function setUserGroupId($userGroupId)
    {
        $this->userGroupId = (int)$userGroupId;
    }

    /**
     * @return int[]
     */
    public function getUserGroupsId()
    {
        return $this->userGroupsId;
    }

    /**
     * @param int[] $userGroupsId
     */
    public function setUserGroupsId(array $userGroupsId)
    {
        $this->userGroupsId = $userGroupsId;
    }

    /**
     * @return string
     */
    public function getDateEdit()
    {
        return $this->dateEdit;
    }

    /**
     * @param string $dateEdit
     */
    public function setDateEdit($dateEdit)
    {
        $this->dateEdit = (int)$dateEdit;
    }

    /**
     * @return int
     */
    public function getOtherUserEdit()
    {
        return $this->otherUserEdit;
    }

    /**
     * @param int $otherUserEdit
     */
    public function setOtherUserEdit($otherUserEdit)
    {
        $this->otherUserEdit = (int)$otherUserEdit;
    }

    /**
     * @return int
     */
    public function getOtherUserGroupEdit()
    {
        return $this->otherUserGroupEdit;
    }

    /**
     * @param int $otherUserGroupEdit
     */
    public function setOtherUserGroupEdit($otherUserGroupEdit)
    {
        $this->otherUserGroupEdit = (int)$otherUserGroupEdit;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId)
    {
        $this->accountId = (int)$accountId;
    }

}