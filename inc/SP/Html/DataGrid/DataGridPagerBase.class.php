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

namespace SP\Html\DataGrid;

/**
 * Class DataGridPagerBase para implementar los métodos del paginador
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridPagerBase implements DataGridPagerInterface
{
    /**
     * @var int
     */
    protected $_searchKey = 0;
    /**
     * @var int
     */
    protected $_limitStart = 0;
    /**
     * @var int
     */
    protected $_limitCount = 0;
    /**
     * @var int
     */
    protected $_totalRows = 0;
    /**
     * @var bool
     */
    protected $_filterOn = false;
    /**
     * @var string
     */
    protected $_onClickFunction = '';
    /**
     * @var array
     */
    protected $_onClickArgs = array();


    /**
     * Establecer el campo de la búsqueda
     *
     * @param int $searchKey
     */
    public function setSearchKey($searchKey)
    {
        $this->_searchKey = $searchKey;
    }

    /**
     * Devolver el campo de la búsqueda
     *
     * @return int
     */
    public function getSearchKey()
    {
        return $this->_searchKey;
    }

    /**
     * Establecer el registro de inicio de la página
     *
     * @param int $limitStart
     */
    public function setLimitStart($limitStart)
    {
        $this->_limitStart = $limitStart;
    }

    /**
     * Devolver el registro de inicio de la página
     *
     * @return int
     */
    public function getLimitStart()
    {
        return $this->_limitStart;
    }

    /**
     * Establecer el número de registros en una página
     *
     * @param int $limitCount
     */
    public function setLimitCount($limitCount)
    {
        $this->_limitCount = $limitCount;
    }

    /**
     * Devolver el número de registros en una página
     *
     * @return mixed
     */
    public function getLimitCount()
    {
        return $this->_limitCount;
    }

    /**
     * Devolver el número de página inicial
     *
     * @return int
     */
    public function getFirstPage()
    {
        return ceil(($this->_limitStart + 1) / $this->_limitCount);
    }

    /**
     * Devolver el número de página final
     *
     * @return int
     */
    public function getLastPage()
    {
        return ceil($this->_totalRows / $this->_limitCount);
    }

    /**
     * Establecer el número total de registros obtenidos
     *
     * @param int $totalRows
     */
    public function setTotalRows($totalRows)
    {
        $this->_totalRows = $totalRows;
    }

    /**
     * Devolver el número total de registros obtenidos
     *
     * @return int
     */
    public function getTotalRows()
    {
        return $this->_totalRows;
    }

    /**
     * Establecer si está activado el filtro
     *
     * @param bool $filterOn
     */
    public function setFilterOn($filterOn)
    {
        $this->_filterOn = $filterOn;
    }

    /**
     * Devolver si está activado el filtro
     *
     * @return bool
     */
    public function getFilterOn()
    {
        return $this->_filterOn;
    }

    /**
     * Establecer la función javascript para paginar
     *
     * @param string $function
     */
    public function setOnClickFunction($function)
    {
        $this->_onClickFunction = $function;
    }

    /**
     * Devolver la función javascript para paginar
     *
     * @return string
     */
    public function getOnClick()
    {
        $args = array();

        foreach ($this->_onClickArgs as $arg) {
            $args[] = (!is_numeric($arg) && $arg !== 'this') ? '\'' . $arg . '\'' : $arg;
        }

        return $this->_onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * Establecer los argumentos de la función OnClick
     *
     * @param string $args
     */
    public function setOnClickArgs($args)
    {
        $this->_onClickArgs[] = $args;
    }

    /**
     * Devolver la funcion para ir a la primera página
     *
     * @return string
     */
    public function getOnClickFirst()
    {
        return $this->_onClickFunction . '(' . $this->_searchKey .', 0, 1)';
    }

    /**
     * Devolver la funcion para ir a la última página
     *
     * @return string
     */
    public function getOnClickLast()
    {
        $limitLast = (($this->_totalRows % $this->_limitCount) == 0) ? $this->_totalRows - $this->_limitCount : floor($this->_totalRows / $this->_limitCount) * $this->_limitCount;

        return $this->_onClickFunction  . '(' . $this->_searchKey . ',' . $limitLast . ',1)';
    }

    /**
     * Devolver la funcion para ir a la siguiente página
     *
     * @return string
     */
    public function getOnClickNext()
    {
        return $this->_onClickFunction . '(' . $this->_searchKey . ',' . ($this->_limitStart + $this->_limitCount) . ',1)';
    }

    /**
     * Devolver la funcion para ir a la página anterior
     *
     * @return string
     */
    public function getOnClickPrev()
    {
        return $this->_onClickFunction  .'(' . $this->_searchKey . ',' . ($this->_limitStart - $this->_limitCount) . ',1)';
    }

}