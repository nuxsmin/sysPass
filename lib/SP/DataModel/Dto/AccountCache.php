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

use SP\DataModel\ItemData;

/**
 * Class AccountCacheDto
 *
 * @package SP\DataModel\Dto
 */
class AccountCache
{
    /**
     * @var int
     */
    private $time;
    /**
     * @var int
     */
    private $accountId;
    /**
     * @var ItemData[]
     */
    private $users;
    /**
     * @var ItemData[]
     */
    private $userGroups;

    /**
     * AccountCacheDto constructor.
     *
     * @param int        $accountId
     * @param ItemData[] $users
     * @param ItemData[] $userGroups
     */
    public function __construct($accountId, array $users, array $userGroups)
    {
        $this->accountId = $accountId;
        $this->users = $users;
        $this->userGroups = $userGroups;
        $this->time = time();
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @return ItemData[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return ItemData[]
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

}