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
 * Class PublickLinkOldData
 *
 * @package SP\DataModel
 */
class PublickLinkOldData
{
    /**
     * @var int
     */
    protected $itemId = 0;
    /**
     * @var int
     */
    protected $userId = 0;
    /**
     * @var string
     */
    protected $linkHash = '';
    /**
     * @var int
     */
    protected $typeId = 0;
    /**
     * @var bool
     */
    protected $notify = false;
    /**
     * @var int
     */
    protected $dateAdd = 0;
    /**
     * @var int
     */
    protected $dateExpire = 0;
    /**
     * @var string
     */
    protected $pass = '';
    /**
     * @var string
     */
    protected $passIV = '';
    /**
     * @var int
     */
    protected $countViews = 0;
    /**
     * @var int
     */
    protected $maxCountViews = 0;
    /**
     * @var array
     */
    protected $useInfo = [];
    /**
     * @var string
     */
    protected $data;

    /**
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @param int $itemId
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

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
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getLinkHash()
    {
        return $this->linkHash;
    }

    /**
     * @param string $linkHash
     */
    public function setLinkHash($linkHash)
    {
        $this->linkHash = $linkHash;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param int $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     * @return boolean
     */
    public function isNotify()
    {
        return (bool)$this->notify;
    }

    /**
     * @param boolean $notify
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
    }

    /**
     * @return int
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * @param int $dateAdd
     */
    public function setDateAdd($dateAdd)
    {
        $this->dateAdd = $dateAdd;
    }

    /**
     * @return int
     */
    public function getDateExpire()
    {
        return $this->dateExpire;
    }

    /**
     * @param int $dateExpire
     */
    public function setDateExpire($dateExpire)
    {
        $this->dateExpire = $dateExpire;
    }

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
    public function getPassIV()
    {
        return $this->passIV;
    }

    /**
     * @param string $passIV
     */
    public function setPassIV($passIV)
    {
        $this->passIV = $passIV;
    }

    /**
     * @return int
     */
    public function getCountViews()
    {
        return $this->countViews;
    }

    /**
     * @param int $countViews
     */
    public function setCountViews($countViews)
    {
        $this->countViews = $countViews;
    }

    /**
     * @return int
     */
    public function addCountViews()
    {
        return $this->countViews++;
    }

    /**
     * @return int
     */
    public function getMaxCountViews()
    {
        return $this->maxCountViews;
    }

    /**
     * @param int $maxCountViews
     */
    public function setMaxCountViews($maxCountViews)
    {
        $this->maxCountViews = $maxCountViews;
    }

    /**
     * @return array
     */
    public function getUseInfo()
    {
        return $this->useInfo;
    }

    /**
     * @param array $useInfo
     */
    public function setUseInfo(array $useInfo)
    {
        $this->useInfo = $useInfo;
    }

    /**
     * @param array $useInfo
     */
    public function addUseInfo($useInfo)
    {
        $this->useInfo[] = $useInfo;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}