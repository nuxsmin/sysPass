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
    public $userName;
    /**
     * @var string
     */
    public $userLogin;
    /**
     * @var string
     */
    public $accountName;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->accountName;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * @return string
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

    /**
     * @return false|string
     */
    public function getDateAddFormat()
    {
        return DateUtil::getDateFromUnix($this->dateAdd);
    }

    /**
     * @return false|string
     */
    public function getDateExpireFormat()
    {
        return DateUtil::getDateFromUnix($this->dateExpire);
    }

    /**
     * @return string
     */
    public function getCountViewsString()
    {
        return sprintf('%d/%d/%d', $this->getCountViews(), $this->getMaxCountViews(), $this->getTotalCountViews());
    }
}