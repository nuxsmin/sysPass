<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
    /**
     * @param  string|null  $seachString
     * @param  int|null  $limitStart
     * @param  int|null  $limitCount
     */
    public function __construct(
        private ?string $seachString = null,
        private ?int $limitStart = 0,
        private ?int $limitCount = 0,
    ) {
        if (!empty($seachString)) {
            $this->seachString = Filter::safeSearchString($seachString);
        }
    }

    public function getSeachString(): ?string
    {
        return $this->seachString;
    }

    public function getLimitStart(): int
    {
        return $this->limitStart;
    }

    public function getLimitCount(): int
    {
        return $this->limitCount;
    }
}
