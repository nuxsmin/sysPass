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

use SP\Util\Filter;


/**
 * Class ItemSearchData
 *
 * @package SP\DataModel
 */
class ItemSearchData
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * @var string
     */
    public $seachString = '';
    /**
     * @var int
     */
    public $limitStart = 0;
    /**
     * @var int
     */
    public $limitCount = 0;
    /**
     * @var string
     */
    public $order = self::ORDER_ASC;

    /**
     * @return string
     */
    public function getSeachString()
    {
        return $this->seachString;
    }

    /**
     * @param string $seachString
     */
    public function setSeachString($seachString)
    {
        $this->seachString = Filter::safeSearchString($seachString);
    }

    /**
     * @return int
     */
    public function getLimitStart()
    {
        return $this->limitStart;
    }

    /**
     * @param int $limitStart
     */
    public function setLimitStart($limitStart)
    {
        $this->limitStart = (int)$limitStart;
    }

    /**
     * @return int
     */
    public function getLimitCount()
    {
        return $this->limitCount;
    }

    /**
     * @param int $limitCount
     */
    public function setLimitCount($limitCount)
    {
        $this->limitCount = (int)$limitCount;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
}