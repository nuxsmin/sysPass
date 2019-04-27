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


/**
 * Class ConfigRequest
 *
 * @package SP\DataModel\Dto
 */
class ConfigRequest
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param $param
     * @param $value
     */
    public function add($param, $value)
    {
        $this->data[$param] = $value;
    }

    /**
     * @param $param
     *
     * @return mixed|null
     */
    public function get($param)
    {
        return isset($this->data[$param]) ? $this->data[$param] : null;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}