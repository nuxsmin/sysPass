<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
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
    private const ORDER_ASC = 'ASC';
    private const ORDER_DESC = 'DESC';

    public ?string $seachString = null;
    public int $limitStart = 0;
    public int $limitCount = 0;
    public string $order = self::ORDER_ASC;

    public function getSeachString(): ?string
    {
        return $this->seachString;
    }

    public function setSeachString(?string $seachString): void
    {
        if ($seachString) {
            $this->seachString = Filter::safeSearchString($seachString);
        } else {
            $this->seachString = null;
        }
    }

    public function getLimitStart(): int
    {
        return $this->limitStart;
    }

    public function setLimitStart(int $limitStart): void
    {
        $this->limitStart = $limitStart;
    }

    public function getLimitCount(): int
    {
        return $this->limitCount;
    }

    public function setLimitCount(int $limitCount): void
    {
        $this->limitCount = $limitCount;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): void
    {
        $this->order = $order;
    }
}