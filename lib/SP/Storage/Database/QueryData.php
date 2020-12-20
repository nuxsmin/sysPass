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

namespace SP\Storage\Database;

use SP\DataModel\DataModelBase;

/**
 * Class QueryData
 *
 * @package SP\Storage
 */
final class QueryData
{
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var string
     */
    protected $query;
    /**
     * @var string
     */
    protected $mapClassName;
    /**
     * @var bool
     */
    protected $useKeyPair = false;
    /**
     * @var string
     */
    protected $select;
    /**
     * @var string
     */
    protected $from;
    /**
     * @var string
     */
    protected $where;
    /**
     * @var string
     */
    protected $groupBy;
    /**
     * @var string|null
     */
    protected $order;
    /**
     * @var string
     */
    protected $limit;
    /**
     * @var string
     */
    protected $queryCount;
    /**
     * @var int Código de estado tras realizar la consulta
     */
    protected $queryStatus = 0;
    /**
     * @var string
     */
    protected $onErrorMessage;

    /**
     * Añadir un parámetro a la consulta
     *
     * @param $value
     * @param $name
     */
    public function addParam($value, $name = null)
    {
        if (null !== $name) {
            $this->params[$name] = $value;
        } else {
            $this->params[] = $value;
        }
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Establecer los parámetros de la consulta
     *
     * @param array $data
     */
    public function setParams(array $data)
    {
        $this->params = $data;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        if (empty($this->query)) {
            return $this->select . ' ' . $this->from . ' ' . $this->where . ' ' . $this->groupBy . ' ' . $this->order . ' ' . $this->limit;
        }

        return $this->query;
    }

    /**
     * @param $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return string|null
     */
    public function getMapClassName(): ?string
    {
        return $this->mapClassName;
    }

    /**
     * @param string $mapClassName
     */
    public function setMapClassName(string $mapClassName)
    {
        $this->mapClassName = $mapClassName;
    }

    /**
     * @return bool
     */
    public function isUseKeyPair(): bool
    {
        return $this->useKeyPair;
    }

    /**
     * @param boolean $useKeyPair
     */
    public function setUseKeyPair(bool $useKeyPair)
    {
        $this->useKeyPair = $useKeyPair;
    }

    /**
     * @return string|null
     */
    public function getSelect(): ?string
    {
        return $this->select;
    }

    /**
     * @param string $select
     */
    public function setSelect(string $select)
    {
        $this->select = 'SELECT ' . $select;
    }

    /**
     * @return string|null
     */
    public function getOrder(): ?string
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder(string $order)
    {
        if (!empty($order)) {
            $this->order = 'ORDER BY ' . $order;
        }
    }

    /**
     * @return string|null
     */
    public function getLimit(): ?string
    {
        return $this->limit;
    }

    /**
     * @param string     $limit
     * @param array|null $params
     */
    public function setLimit(string $limit, ?array $params = null)
    {
        if (!empty($limit)) {
            $this->limit = 'LIMIT ' . $limit;

            if ($params !== null) {
                $this->addParams($params);
            }
        }
    }

    /**
     * Añadir parámetros a la consulta
     *
     * @param array $params
     */
    public function addParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @return string
     */
    public function getQueryCount(): string
    {
        if (empty($this->queryCount)) {
            return 'SELECT COUNT(*) ' . $this->from . ' ' . $this->where;
        }

        return $this->queryCount;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string $from
     */
    public function setFrom(string $from)
    {
        if (!empty($from)) {
            $this->from = 'FROM ' . $from;
        }
    }

    /**
     * @return string|null
     */
    public function getWhere(): ?string
    {
        return $this->where;
    }

    /**
     * @param array|string $where
     */
    public function setWhere($where)
    {
        if (!empty($where)) {
            if (is_array($where)) {
                $this->where = 'WHERE ' . implode(' AND ', $where);
            } else {
                $this->where = 'WHERE ' . $where;
            }
        }
    }

    /**
     * @return string
     */
    public function getOnErrorMessage(): string
    {
        return $this->onErrorMessage ?: __u('Error while querying');
    }

    /**
     * @param string $onErrorMessage
     */
    public function setOnErrorMessage(string $onErrorMessage)
    {
        $this->onErrorMessage = $onErrorMessage;
    }

    /**
     * @return string|null
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @param string $groupBy
     */
    public function setGroupBy(string $groupBy)
    {
        if (!empty($groupBy)) {
            $this->groupBy = 'GROUP BY ' . $groupBy;
        }
    }
}