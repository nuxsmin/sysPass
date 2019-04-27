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

namespace SP\DataModel\Dto;

use SP\DataModel\AccountSearchVData;

/**
 * Class AccountSearchResponse
 *
 * @package SP\DataModel\Dto
 */
class AccountSearchResponse
{
    /**
     * @var int
     */
    private $count;
    /**
     * @var AccountSearchVData[]
     */
    private $data;

    /**
     * AccountSearchDto constructor.
     *
     * @param int                  $count
     * @param AccountSearchVData[] $data
     */
    public function __construct($count, array $data)
    {
        $this->count = $count;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return AccountSearchVData[]
     */
    public function getData()
    {
        return $this->data;
    }
}