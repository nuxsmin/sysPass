<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Storage;

use SP\DataModel\DataModelBase;

/**
 * Class QueryData
 *
 * @package SP\Storage
 */
class QueryData
{
    /**
     * @var array
     */
    protected $data = array();
    /**
     * @var string
     */
    protected $query = '';
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
     * @param $value
     * @param $name
     */
    public function addParam($value, $name = null)
    {
        if (null !== $name) {
            $this->data[$name] = $value;
        } else {
            $this->data[] = $value;
        }
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
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
     * @return DataModelBase
     */
    public function getMapClass()
    {
        return $this->mapClass;
    }

    /**
     * @param DataModelBase $mapClass
     */
    public function setMapClass(DataModelBase $mapClass)
    {
        $this->mapClass = $mapClass;
    }

    /**
     * @return boolean
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
}