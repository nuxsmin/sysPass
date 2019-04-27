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

use RuntimeException;

/**
 * Class QueryCondition
 *
 * @package SP\Mvc\Model
 */
final class QueryCondition
{
    const CONDITION_AND = ' AND ';
    const CONDITION_OR = ' OR ';

    /**
     * @var array
     */
    protected $query = [];
    /**
     * @var array
     */
    protected $param = [];

    /**
     * @param string $query
     * @param array  $params
     *
     * @return QueryCondition
     */
    public function addFilter($query, array $params = null)
    {
        $this->query[] = "($query)";

        if ($params !== null) {
            $this->param = array_merge($this->param, $params);
        }

        return $this;
    }

    /**
     * @param string $type
     *
     * @return string|null
     */
    public function getFilters($type = self::CONDITION_AND)
    {
        if ($type !== self::CONDITION_AND && $type !== self::CONDITION_OR) {
            throw new RuntimeException(__u('Invalid filter type'));
        }

        return $this->hasFilters() ? '(' . implode($type, $this->query) . ')' : null;
    }

    /**
     * @return bool
     */
    public function hasFilters()
    {
        return !empty($this->query);
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
    public function getFiltersCount()
    {
        return count($this->query);
    }
}