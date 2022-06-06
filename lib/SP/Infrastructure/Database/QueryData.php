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

namespace SP\Infrastructure\Database;

/**
 * Class QueryData
 *
 * @package SP\Storage
 */
final class QueryData
{
    protected array   $params         = [];
    protected ?string $query          = null;
    protected ?string $mapClassName   = null;
    protected bool    $useKeyPair     = false;
    protected ?string $select         = null;
    protected ?string $from           = null;
    protected ?string $where          = null;
    protected ?string $groupBy        = null;
    protected ?string $order          = null;
    protected ?string $limit          = null;
    protected ?string $queryCount     = null;
    protected ?string $onErrorMessage = null;

    /**
     * Añadir un parámetro a la consulta
     *
     * @param $value
     * @param $name
     */
    public function addParam($value, $name = null): void
    {
        if (null !== $name) {
            $this->params[$name] = $value;
        } else {
            $this->params[] = $value;
        }
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $data): void
    {
        $this->params = $data;
    }

    public function getQuery(): string
    {
        if (empty($this->query)) {
            return $this->select.
                   ' '.
                   $this->from.
                   ' '.
                   $this->where.
                   ' '.
                   $this->groupBy.
                   ' '.
                   $this->order.
                   ' '.
                   $this->limit;
        }

        return $this->query;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getMapClassName(): ?string
    {
        return $this->mapClassName;
    }

    public function setMapClassName(string $mapClassName): void
    {
        $this->mapClassName = $mapClassName;
    }

    public function isUseKeyPair(): bool
    {
        return $this->useKeyPair;
    }

    public function setUseKeyPair(bool $useKeyPair): void
    {
        $this->useKeyPair = $useKeyPair;
    }

    public function getSelect(): ?string
    {
        return $this->select;
    }

    public function setSelect(string $select): void
    {
        $this->select = 'SELECT '.$select;
    }

    public function setOrder(string $order): void
    {
        if (!empty($order)) {
            $this->order = 'ORDER BY '.$order;
        }
    }

    public function setLimit(string $limit, ?array $params = null): void
    {
        if (!empty($limit)) {
            $this->limit = 'LIMIT '.$limit;

            if ($params !== null) {
                $this->addParams($params);
            }
        }
    }

    public function addParams(array $params): void
    {
        $this->params = array_merge($this->params, $params);
    }

    public function getQueryCount(): string
    {
        if (empty($this->queryCount)) {
            return 'SELECT COUNT(*) '.$this->from.' '.$this->where;
        }

        return $this->queryCount;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function setFrom(string $from): void
    {
        if (!empty($from)) {
            $this->from = 'FROM '.$from;
        }
    }

    public function getWhere(): ?string
    {
        return $this->where;
    }

    /**
     * @param  string[]|string  $where
     */
    public function setWhere($where): void
    {
        if (!empty($where)) {
            if (is_array($where)) {
                $this->where = 'WHERE '.implode(' AND ', $where);
            } else {
                $this->where = 'WHERE '.$where;
            }
        }
    }

    public function getOnErrorMessage(): string
    {
        return $this->onErrorMessage ?: __u('Error while querying');
    }

    public function setOnErrorMessage(string $onErrorMessage): void
    {
        $this->onErrorMessage = $onErrorMessage;
    }

    public function setGroupBy(string $groupBy): void
    {
        if (!empty($groupBy)) {
            $this->groupBy = 'GROUP BY '.$groupBy;
        }
    }
}