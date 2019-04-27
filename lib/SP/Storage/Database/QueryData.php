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
    protected $mapClassName = '';
    /**
     * @var DataModelBase
     */
    protected $mapClass;
    /**
     * @var bool
     */
    protected $useKeyPair = false;
    /**
     * @var string
     */
    protected $select = '';
    /**
     * @var string
     */
    protected $from = '';
    /**
     * @var string
     */
    protected $where = '';
    /**
     * @var string
     */
    protected $groupBy = '';
    /**
     * @var string
     */
    protected $order = '';
    /**
     * @var string
     */
    protected $limit = '';
    /**
     * @var string
     */
    protected $queryCount = '';
    /**
     * @var int
     */
    protected $queryNumRows = 0;
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
    public function getParams()
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
    public function getQuery()
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
     * @return string
     */
    public function getMapClassName()
    {
        return $this->mapClassName;
    }

    /**
     * @param string $mapClassName
     */
    public function setMapClassName($mapClassName)
    {
        $this->mapClassName = $mapClassName;
    }

    /**
     * @return bool
     */
    public function isUseKeyPair()
    {
        return $this->useKeyPair;
    }

    /**
     * @param boolean $useKeyPair
     */
    public function setUseKeyPair($useKeyPair)
    {
        $this->useKeyPair = (bool)$useKeyPair;
    }

    /**
     * @return string
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @param string $select
     */
    public function setSelect($select)
    {
        $this->select = 'SELECT ' . $select;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder($order)
    {
        if (!empty($order)) {
            $this->order = 'ORDER BY ' . $order;
        }
    }

    /**
     * @return string
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param string     $limit
     * @param array|null $params
     */
    public function setLimit($limit, array $params = null)
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
    public function getQueryCount()
    {
        if (empty($this->queryCount)) {
            return 'SELECT COUNT(*) ' . $this->from . ' ' . $this->where;
        }

        return $this->queryCount;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     */
    public function setFrom($from)
    {
        if (!empty($from)) {
            $this->from = 'FROM ' . $from;
        }
    }

    /**
     * @return string
     */
    public function getWhere()
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
     * @return int
     */
    public function getQueryNumRows()
    {
        return (int)$this->queryNumRows;
    }

    /**
     * @param int $queryNumRows
     */
    public function setQueryNumRows($queryNumRows)
    {
        $this->queryNumRows = (int)$queryNumRows;
    }

    /**
     * @return int
     */
    public function getQueryStatus()
    {
        return $this->queryStatus;
    }

    /**
     * @param int $queryStatus
     */
    public function setQueryStatus($queryStatus)
    {
        $this->queryStatus = $queryStatus;
    }

    /**
     * @return string
     */
    public function getOnErrorMessage()
    {
        return $this->onErrorMessage ?: __u('Error while querying');
    }

    /**
     * @param string $onErrorMessage
     */
    public function setOnErrorMessage($onErrorMessage)
    {
        $this->onErrorMessage = $onErrorMessage;
    }

    /**
     * @return string
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @param string $groupBy
     */
    public function setGroupBy($groupBy)
    {
        if (!empty($groupBy)) {
            $this->groupBy = 'GROUP BY ' . $groupBy;
        }
    }
}