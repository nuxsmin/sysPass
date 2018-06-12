<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Html\DataGrid;

use SP\Html\Assets\IconInterface;
use SP\Storage\Database\QueryResult;

defined('APP_ROOT') || die();

/**
 * Class DataGridDataBase para establecer el origen de datos de la matriz
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridDataBase implements DataGridDataInterface
{
    /**
     * Los datos de la matriz
     *
     * @var array
     */
    private $_data = [];
    /**
     * Las columnas a mostrar de los datos obtenidos
     *
     * @var array
     */
    private $_sources = [];
    /**
     * La columna que identifica cada elemento de los datos de la matriz
     *
     * @var int
     */
    private $_sourceId = 0;
    /**
     * Las columnas a mostrar de los datos obtenidos que son representadas con iconos
     *
     * @var array
     */
    private $_sourcesWithIcon = [];
    /**
     * @var int
     */
    private $_dataCount = 0;

    /**
     * @return array
     */
    public function getDataRowSourcesWithIcon()
    {
        return $this->_sourcesWithIcon;
    }

    /**
     * @param string   $source
     * @param bool     $isMethod
     * @param callable $filter
     */
    public function addDataRowSource($source, $isMethod = false, callable $filter = null)
    {
        $this->_sources[] = [
            'name' => $source,
            'isMethod' => $isMethod,
            'filter' => $filter
        ];
    }

    /**
     * @param $id string
     */
    public function setDataRowSourceId($id)
    {
        $this->_sourceId = $id;
    }

    /**
     * @return array
     */
    public function getDataRowSources()
    {
        return $this->_sources;
    }

    /**
     * @return string
     */
    public function getDataRowSourceId()
    {
        return $this->_sourceId;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @param QueryResult $queryResult
     */
    public function setData(QueryResult $queryResult)
    {
        $this->_dataCount = $queryResult->getTotalNumRows();
        $this->_data = $queryResult->getDataAsArray();
    }

    /**
     * @param string        $source
     * @param IconInterface $icon
     * @param mixed         $value Valor para mostrar el icono
     */
    public function addDataRowSourceWithIcon($source, IconInterface $icon, $value = 1)
    {
        $this->_sourcesWithIcon[] = [
            'field' => $source,
            'icon' => $icon,
            'value' => $value
        ];
    }

    /**
     * Devolver el número de elementos obtenidos
     *
     * @return int
     */
    public function getDataCount()
    {
        return $this->_dataCount;
    }
}