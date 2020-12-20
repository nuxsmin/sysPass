<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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
     * @return string|null
     */
    public function getSeachString(): ?string
    {
        return $this->seachString;
    }

    /**
     * @param string|null $seachString
     */
    public function setSeachString(?string $seachString)
    {
        if ($seachString) {
            $this->seachString = Filter::safeSearchString($seachString);
        } else {
            $this->seachString = null;
        }
    }

    /**
     * @return int
     */
    public function getLimitStart(): int
    {
        return $this->limitStart;
    }

    /**
     * @param int|null $limitStart
     */
    public function setLimitStart(?int $limitStart)
    {
        $this->limitStart = (int)$limitStart;
    }

    /**
     * @return int
     */
    public function getLimitCount(): int
    {
        return $this->limitCount;
    }

    /**
     * @param int|null $limitCount
     */
    public function setLimitCount(?int $limitCount)
    {
        $this->limitCount = (int)$limitCount;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @param string|null $order
     */
    public function setOrder(?string $order)
    {
        $this->order = $order;
    }
}