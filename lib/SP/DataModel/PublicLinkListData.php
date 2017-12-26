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

use SP\Util\DateUtil;

defined('APP_ROOT') || die();

/**
 * Class PublicLinkListData
 *
 * @package SP\DataModel
 */
class PublicLinkListData extends PublicLinkData
{
    /**
     * @var string
     */
    public $user_name;
    /**
     * @var string
     */
    public $user_login;
    /**
     * @var string
     */
    public $account_name;

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->user_login;
    }

    /**
     * @return string
     */
    public function getAccountName()
    {
        return $this->account_name;
    }

    /**
     * @return string
     */
    public function getNotifyString()
    {
        return $this->isPublicLinkNotify() ? __u('ON') : __u('OFF');
    }

    /**
     * @return false|string
     */
    public function getDateAddFormat()
    {
        return DateUtil::getDateFromUnix($this->publicLink_dateAdd);
    }

    /**
     * @return false|string
     */
    public function getDateExpireFormat()
    {
        return DateUtil::getDateFromUnix($this->publicLink_dateExpire);
    }

    /**
     * @return string
     */
    public function getCountViewsString()
    {
        return sprintf('%d/%d/%d', $this->getPublicLinkCountViews(), $this->getPublicLinkMaxCountViews(), $this->getPublicLinkTotalCountViews());
    }
}