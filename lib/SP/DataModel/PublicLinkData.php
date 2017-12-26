<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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
    public $publicLink_id = 0;
    /**
     * @var int
     */
    public $publicLink_itemId = 0;
    /**
     * @var string
     */
    public $publicLink_hash = '';
    /**
     * @var int
     */
    public $publicLink_userId = 0;
    /**
     * @var int
     */
    public $publicLink_typeId = 0;
    /**
     * @var bool
     */
    public $publicLink_notify = false;
    /**
     * @var int
     */
    public $publicLink_dateAdd = 0;
    /**
     * @var int
     */
    public $publicLink_dateExpire = 0;
    /**
     * @var int
     */
    public $publicLink_countViews = 0;
    /**
     * @var int
     */
    public $publicLink_totalCountViews = 0;
    /**
     * @var int
     */
    public $publicLink_maxCountViews = 0;
    /**
     * @var array|string
     */
    public $publicLink_useInfo;
    /**
     * @var string
     */
    public $publicLink_data;

    /**
     * @return string
     */
    public function getPublicLinkData()
    {
        return $this->publicLink_data;
    }

    /**
     * @param string $publicLink_data
     */
    public function setPublicLinkData($publicLink_data)
    {
        $this->publicLink_data = $publicLink_data;
    }

    /**
     * @return int
     */
    public function getPublicLinkId()
    {
        return (int)$this->publicLink_id;
    }

    /**
     * @param int $publicLink_id
     */
    public function setPublicLinkId($publicLink_id)
    {
        $this->publicLink_id = (int)$publicLink_id;
    }

    /**
     * @return string
     */
    public function getPublicLinkHash()
    {
        return $this->publicLink_hash;
    }

    /**
     * @param string $publicLink_hash
     */
    public function setPublicLinkHash($publicLink_hash)
    {
        $this->publicLink_hash = $publicLink_hash;
    }

    /**
     * @return int
     */
    public function getPublicLinkItemId()
    {
        return (int)$this->publicLink_itemId;
    }

    /**
     * @param int $publicLink_itemId
     */
    public function setPublicLinkItemId($publicLink_itemId)
    {
        $this->publicLink_itemId = (int)$publicLink_itemId;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->publicLink_id;
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
    public function getPublicLinkUserId()
    {
        return (int)$this->publicLink_userId;
    }

    /**
     * @param int $publicLink_userId
     */
    public function setPublicLinkUserId($publicLink_userId)
    {
        $this->publicLink_userId = (int)$publicLink_userId;
    }

    /**
     * @return int
     */
    public function getPublicLinkTypeId()
    {
        return (int)$this->publicLink_typeId;
    }

    /**
     * @param int $publicLink_typeId
     */
    public function setPublicLinkTypeId($publicLink_typeId)
    {
        $this->publicLink_typeId = (int)$publicLink_typeId;
    }

    /**
     * @return boolean
     */
    public function isPublicLinkNotify()
    {
        return (bool)$this->publicLink_notify;
    }

    /**
     * @param boolean $publicLink_notify
     */
    public function setPublicLinkNotify($publicLink_notify)
    {
        $this->publicLink_notify = (bool)$publicLink_notify;
    }

    /**
     * @return int
     */
    public function getPublicLinkDateAdd()
    {
        return (int)$this->publicLink_dateAdd;
    }

    /**
     * @param int $publicLink_dateAdd
     */
    public function setPublicLinkDateAdd($publicLink_dateAdd)
    {
        $this->publicLink_dateAdd = (int)$publicLink_dateAdd;
    }

    /**
     * @return int
     */
    public function getPublicLinkDateExpire()
    {
        return (int)$this->publicLink_dateExpire;
    }

    /**
     * @param int $publicLink_dateExpire
     */
    public function setPublicLinkDateExpire($publicLink_dateExpire)
    {
        $this->publicLink_dateExpire = (int)$publicLink_dateExpire;
    }

    /**
     * @return int
     */
    public function getPublicLinkCountViews()
    {
        return (int)$this->publicLink_countViews;
    }

    /**
     * @param int $publicLink_countViews
     */
    public function setPublicLinkCountViews($publicLink_countViews)
    {
        $this->publicLink_countViews = (int)$publicLink_countViews;
    }

    /**
     * @return int
     */
    public function addCountViews()
    {
        return $this->publicLink_countViews++;
    }

    /**
     * @return int
     */
    public function getPublicLinkMaxCountViews()
    {
        return (int)$this->publicLink_maxCountViews;
    }

    /**
     * @param int $publicLink_maxCountViews
     */
    public function setPublicLinkMaxCountViews($publicLink_maxCountViews)
    {
        $this->publicLink_maxCountViews = (int)$publicLink_maxCountViews;
    }

    /**
     * @return array
     */
    public function getPublicLinkUseInfo()
    {
        if (is_string($this->publicLink_useInfo)) {
            return unserialize($this->publicLink_useInfo);
        }

        return (array)$this->publicLink_useInfo;
    }

    /**
     * @param array $publicLink_useInfo
     */
    public function setPublicLinkUseInfo(array $publicLink_useInfo)
    {
        $this->publicLink_useInfo = $publicLink_useInfo;
    }

    /**
     * @return int
     */
    public function getPublicLinkTotalCountViews()
    {
        return (int)$this->publicLink_totalCountViews;
    }

    /**
     * @return int
     */
    public function addTotalCountViews()
    {
        return $this->publicLink_totalCountViews++;
    }
}