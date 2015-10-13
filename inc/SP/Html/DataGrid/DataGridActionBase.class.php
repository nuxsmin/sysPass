<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 * Class DataGridAction para crear una acción para cada elemento de la matriz de datos
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridActionBase implements DataGridActionInterface
{
    /**
     * El nombre de la acción
     *
     * @var string
     */
    private $_name = '';
    /**
     * El título de la acción
     *
     * @var string
     */
    private $_title = '';
    /**
     * El id de la acción
     *
     * @var int
     */
    private $_id = 0;
    /**
     * La función javascript del evento OnClick
     * @var string
     */
    private $_onClickFunction = '';
    /**
     * Los argumentos de la función OnClick
     *
     * @var array
     */
    private $_onClickArgs = array();
    /**
     * El icono de la acción
     *
     * @var DataGridIcon
     */
    private $_icon = null;
    /**
     * Si es una acción de eliminar elementos
     *
     * @var bool
     */
    private $_isDelete = false;
    /**
     * Si se debe de omitir para los elementos del listado
     *
     * @var bool
     */
    private $_isSkip = false;
    /**
     * Si es una acción para crear elementos
     *
     * @var bool
     */
    private $_isNew = false;
    /**
     * La columna de origen de datos que condiciona esta acción
     *
     * @var string
     */
    private $_filterRowSource = '';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $name string
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param $title string
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @param $function string
     */
    public function setOnClickFunction($function)
    {
        $this->_onClickFunction = $function;
    }

    /**
     * @param $args string
     */
    public function setOnClickArgs($args)
    {
        $this->_onClickArgs[] = $args;
    }

    /**
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
     * @return DataGridIcon
     */
    public function getIcon()
    {
        return $this->_icon;
    }

    /**
     * @param $icon DataGridIcon
     */
    public function setIcon($icon)
    {
        $this->_icon = $icon;
    }

    /**
     * @param $skip bool
     */
    public function setSkip($skip)
    {
        $this->_isSkip = $skip;
    }

    /**
     * @return bool
     */
    public function isSkip()
    {
        return $this->_isSkip;
    }

    /**
     * @param $delete bool
     */
    public function setIsDelete($delete)
    {
        $this->_isDelete = $delete;
    }

    /**
     * @return bool
     */
    public function isDelete()
    {
        return $this->_isDelete;
    }

    /**
     * @param bool $new
     */
    public function setIsNew($new)
    {
        $this->_isNew = $new;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->_isNew;
    }

    /**
     * @param $rowSource string
     */
    public function setFilterRowSource($rowSource)
    {
        $this->_filterRowSource = $rowSource;
    }

    /**
     * @return string
     */
    public function getFilterRowSource()
    {
        return $this->_filterRowSource;
    }
}