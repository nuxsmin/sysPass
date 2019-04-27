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
    public $id = 0;
    /**
     * @var string
     */
    public $pass;
    /**
     * @var string
     */
    public $hashSalt;
    /**
     * @var string
     */
    public $mPass;
    /**
     * @var string
     */
    public $mKey;
    /**
     * @var int
     */
    public $lastUpdateMPass = 0;

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    /**
     * @return string
     */
    public function getHashSalt()
    {
        return $this->hashSalt;
    }

    /**
     * @param string $hashSalt
     */
    public function setHashSalt($hashSalt)
    {
        $this->hashSalt = $hashSalt;
    }

    /**
     * @return string
     */
    public function getMPass()
    {
        return $this->mPass;
    }

    /**
     * @param string $mPass
     */
    public function setMPass($mPass)
    {
        $this->mPass = $mPass;
    }

    /**
     * @return string
     */
    public function getMKey()
    {
        return $this->mKey;
    }

    /**
     * @param string $mKey
     */
    public function setMKey($mKey)
    {
        $this->mKey = $mKey;
    }

    /**
     * @return int
     */
    public function getLastUpdateMPass()
    {
        return (int)$this->lastUpdateMPass;
    }

    /**
     * @param int $lastUpdateMPass
     */
    public function setLastUpdateMPass($lastUpdateMPass)
    {
        $this->lastUpdateMPass = (int)$lastUpdateMPass;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }
}