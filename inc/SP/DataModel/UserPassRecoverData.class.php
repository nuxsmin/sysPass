<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel;

/**
 * Class UserPassRecoverData
 *
 * @package SP\DataModel
 */
class UserPassRecoverData extends DataModelBase
{
    /**
     * @var int
     */
    public $userpassr_userId = 0;
    /**
     * @var string
     */
    public $userpassr_hash = '';
    /**
     * @var int
     */
    public $userpassr_date = 0;
    /**
     * @var bool
     */
    public $userpassr_used = 0;

    /**
     * @return int
     */
    public function getUserpassrUserId()
    {
        return (int)$this->userpassr_userId;
    }

    /**
     * @param int $userpassr_userId
     */
    public function setUserpassrUserId($userpassr_userId)
    {
        $this->userpassr_userId = (int)$userpassr_userId;
    }

    /**
     * @return string
     */
    public function getUserpassrHash()
    {
        return $this->userpassr_hash;
    }

    /**
     * @param string $userpassr_hash
     */
    public function setUserpassrHash($userpassr_hash)
    {
        $this->userpassr_hash = $userpassr_hash;
    }

    /**
     * @return int
     */
    public function getUserpassrDate()
    {
        return $this->userpassr_date;
    }

    /**
     * @param int $userpassr_date
     */
    public function setUserpassrDate($userpassr_date)
    {
        $this->userpassr_date = $userpassr_date;
    }

    /**
     * @return boolean
     */
    public function isUserpassrUsed()
    {
        return (int)$this->userpassr_used;
    }

    /**
     * @param boolean $userpassr_used
     */
    public function setUserpassrUsed($userpassr_used)
    {
        $this->userpassr_used = (int)$userpassr_used;
    }
    
}