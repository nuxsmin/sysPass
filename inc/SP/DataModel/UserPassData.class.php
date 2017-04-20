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
 * Class UserPassData
 *
 * @package SP\DataModel
 */
class UserPassData extends DataModelBase
{
    /**
     * @var int
     */
    public $user_id = 0;
    /**
     * @var string
     */
    public $user_pass = '';
    /**
     * @var string
     */
    public $user_hashSalt = '';
    /**
     * @var string
     */
    public $user_mPass = '';
    /**
     * @var string
     */
    public $user_mKey = '';
    /**
     * @var int
     */
    public $user_lastUpdateMPass = 0;

    /**
     * @return string
     */
    public function getUserPass()
    {
        return $this->user_pass;
    }

    /**
     * @param string $user_pass
     */
    public function setUserPass($user_pass)
    {
        $this->user_pass = $user_pass;
    }

    /**
     * @return string
     */
    public function getUserHashSalt()
    {
        return $this->user_hashSalt;
    }

    /**
     * @param string $user_hashSalt
     */
    public function setUserHashSalt($user_hashSalt)
    {
        $this->user_hashSalt = $user_hashSalt;
    }

    /**
     * @return string
     */
    public function getUserMPass()
    {
        return $this->user_mPass;
    }

    /**
     * @param string $user_mPass
     */
    public function setUserMPass($user_mPass)
    {
        $this->user_mPass = $user_mPass;
    }

    /**
     * @return string
     */
    public function getUserMKey()
    {
        return $this->user_mKey;
    }

    /**
     * @param string $user_mKey
     */
    public function setUserMKey($user_mKey)
    {
        $this->user_mKey = $user_mKey;
    }

    /**
     * @return int
     */
    public function getUserLastUpdateMPass()
    {
        return (int)$this->user_lastUpdateMPass;
    }

    /**
     * @param int $user_lastUpdateMPass
     */
    public function setUserLastUpdateMPass($user_lastUpdateMPass)
    {
        $this->user_lastUpdateMPass = (int)$user_lastUpdateMPass;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = (int)$user_id;
    }
}