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

namespace SP\Mvc\Model;

/**
 * Class QueryJoin
 *
 * @package SP\Mvc\Model
 */
final class QueryJoin
{
    /**
     * @var array
     */
    protected $join = [];
    /**
     * @var array
     */
    protected $param = [];

    /**
     * @param string $join
     * @param array  $params
     *
     * @return QueryJoin
     */
    public function addJoin($join, array $params = null)
    {
        $this->join[] = $join;

        if ($params !== null) {
            $this->param = array_merge($this->param, $params);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getJoins()
    {
        return $this->hasJoins() ? implode(PHP_EOL, $this->join) : null;
    }

    /**
     * @return bool
     */
    public function hasJoins()
    {
        return !empty($this->join);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->param;
    }

    /**
     * @return int
     */
    public function getJoinsCount()
    {
        return count($this->join);
    }
}