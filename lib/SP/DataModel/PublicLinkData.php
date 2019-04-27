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

defined('APP_ROOT') || die();

/**
 * Class PublicLinkData
 *
 * @package SP\DataModel
 */
class PublicLinkData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $id = 0;
    /**
     * @var int
     */
    public $itemId = 0;
    /**
     * @var string
     */
    public $hash = '';
    /**
     * @var int
     */
    public $userId = 0;
    /**
     * @var int
     */
    public $typeId = 0;
    /**
     * @var bool
     */
    public $notify = false;
    /**
     * @var int
     */
    public $dateAdd = 0;
    /**
     * @var int
     */
    public $dateUpdate = 0;
    /**
     * @var int
     */
    public $dateExpire = 0;
    /**
     * @var int
     */
    public $countViews = 0;
    /**
     * @var int
     */
    public $totalCountViews = 0;
    /**
     * @var int
     */
    public $maxCountViews = 0;
    /**
     * @var string
     */
    public $useInfo;
    /**
     * @var string
     */
    public $data;

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

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return int
     */
    public function getItemId()
    {
        return (int)$this->itemId;
    }

    /**
     * @param int $itemId
     */
    public function setItemId($itemId)
    {
        $this->itemId = (int)$itemId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return (int)$this->typeId;
    }

    /**
     * @param int $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = (int)$typeId;
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
        $this->notify = (bool)$notify;
    }

    /**
     * @return int
     */
    public function getDateAdd()
    {
        return (int)$this->dateAdd;
    }

    /**
     * @param int $dateAdd
     */
    public function setDateAdd($dateAdd)
    {
        $this->dateAdd = (int)$dateAdd;
    }

    /**
     * @return int
     */
    public function getDateExpire()
    {
        return (int)$this->dateExpire;
    }

    /**
     * @param int $dateExpire
     */
    public function setDateExpire($dateExpire)
    {
        $this->dateExpire = (int)$dateExpire;
    }

    /**
     * @return int
     */
    public function getCountViews()
    {
        return (int)$this->countViews;
    }

    /**
     * @param int $countViews
     */
    public function setCountViews($countViews)
    {
        $this->countViews = (int)$countViews;
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
        return (int)$this->maxCountViews;
    }

    /**
     * @param int $maxCountViews
     */
    public function setMaxCountViews($maxCountViews)
    {
        $this->maxCountViews = (int)$maxCountViews;
    }

    /**
     * @return string
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
        $this->useInfo = serialize($useInfo);
    }

    /**
     * @return int
     */
    public function getTotalCountViews()
    {
        return (int)$this->totalCountViews;
    }

    /**
     * @return int
     */
    public function addTotalCountViews()
    {
        return $this->totalCountViews++;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * @return int
     */
    public function getDateUpdate(): int
    {
        return (int)$this->dateUpdate;
    }

    /**
     * @param int $dateUpdate
     */
    public function setDateUpdate(int $dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;
    }
}