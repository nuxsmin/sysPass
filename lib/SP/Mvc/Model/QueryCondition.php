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

namespace SP\Mvc\Model;

use RuntimeException;

/**
 * Class QueryCondition
 *
 * @package SP\Mvc\Model
 */
final class QueryCondition
{
    public const CONDITION_AND = ' AND ';
    public const CONDITION_OR = ' OR ';

    protected array $query = [];
    protected array $param = [];

    public function addFilter(
        string $query,
        ?array $params = null
    ): QueryCondition
    {
        $this->query[] = "($query)";

        if ($params !== null) {
            $this->param = array_merge($this->param, $params);
        }

        return $this;
    }

    public function getFilters(string $type = self::CONDITION_AND): ?string
    {
        if ($type !== self::CONDITION_AND && $type !== self::CONDITION_OR) {
            throw new RuntimeException(__u('Invalid filter type'));
        }

        return $this->hasFilters()
            ? '(' . implode($type, $this->query) . ')'
            : null;
    }

    public function hasFilters(): bool
    {
        return count($this->query) !== 0;
    }

    public function getParams(): array
    {
        return $this->param;
    }

    public function getFiltersCount(): int
    {
        return count($this->query);
    }
}