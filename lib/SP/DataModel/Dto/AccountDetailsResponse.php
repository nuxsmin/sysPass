<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\DataModel\AccountVData;

/**
 * Class AccountDto
 *
 * @package SP\DataModel\Dto
 */
class AccountDetailsResponse
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var AccountVData
     */
    private $accountVData;
    /**
     * @var array Los Ids de los usuarios secundarios de la cuenta.
     */
    private $users;
    /**
     * @var array Los Ids de los grupos secundarios de la cuenta.
     */
    private $userGroups;
    /**
     * @var array
     */
    private $tags;

    /**
     * AccountDetailsResponse constructor.
     *
     * @param int          $id
     * @param AccountVData $accountVData
     */
    public function __construct($id, AccountVData $accountVData)
    {
        $this->id = $id;
        $this->accountVData = $accountVData;
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param array $users
     */
    public function setUsers(array $users)
    {
        $this->users = $users;
    }

    /**
     * @return array
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * @param array $userGroups
     */
    public function setUserGroups(array $userGroups)
    {
        $this->userGroups = $userGroups;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return AccountVData
     */
    public function getAccountVData()
    {
        return $this->accountVData;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}