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

defined('APP_ROOT') || die();

/**
 * Class PublicLinkListData
 *
 * @package SP\DataModel
 */
class PublicLinkListData extends PublicLinkBaseData
{
    /**
     * @var string
     */
    public $accountName = '';
    /**
     * @var string
     */
    public $userLogin = '';
    /**
     * @var string
     */
    public $notify = '';
    /**
     * @var string
     */
    public $dateAdd = '';
    /**
     * @var string
     */
    public $dateExpire = '';
    /**
     * @var int
     */
    public $countViews = 0;
    /**
     * @var array
     */
    public $useInfo = [];

    /**
     * @return string
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

    /**
     * @param string $accountName
     */
    public function setAccountName($accountName)
    {
        $this->accountName = $accountName;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * @param string $userLogin
     */
    public function setUserLogin($userLogin)
    {
        $this->userLogin = $userLogin;
    }

    /**
     * @return string
     */
    public function getNotify()
    {
        return $this->notify;
    }

    /**
     * @param string $notify
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
    }

    /**
     * @return string
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * @param string $dateAdd
     */
    public function setDateAdd($dateAdd)
    {
        $this->dateAdd = $dateAdd;
    }

    /**
     * @return string
     */
    public function getDateExpire()
    {
        return $this->dateExpire;
    }

    /**
     * @param string $dateExpire
     */
    public function setDateExpire($dateExpire)
    {
        $this->dateExpire = $dateExpire;
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
     * @return array
     */
    public function getUseInfo()
    {
        return $this->useInfo;
    }

    /**
     * @param array $useInfo
     */
    public function setUseInfo($useInfo)
    {
        $this->useInfo = $useInfo;
    }
}