<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

defined('APP_ROOT') || die();

/**
 * Class DataGridAction para crear una acción para cada elemento de la matriz de datos
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridActionBase implements DataGridActionInterface
{
    /**
     * El objeto reflexivo que determina si se muestra la acción
     *
     * @var \ReflectionMethod
     */
    protected $_reflectionFilter;
    /**
     * El nombre de la acción
     *
     * @var string
     */
    protected $_name = '';
    /**
     * El título de la acción
     *
     * @var string
     */
    protected $_title = '';
    /**
     * El id de la acción
     *
     * @var int
     */
    protected $_id = 0;
    /**
     * La función javascript del evento OnClick
     *
     * @var string
     */
    protected $_onClickFunction = '';
    /**
     * Los argumentos de la función OnClick
     *
     * @var array
     */
    protected $_onClickArgs = [];
    /**
     * El icono de la acción
     *
     * @var IconInterface
     */
    protected $_icon;
    /**
     * Si se debe de omitir para los elementos del listado
     *
     * @var bool
     */
    protected $_isSkip = false;
    /**
     * La columna de origen de datos que condiciona esta acción
     *
     * @var string
     */
    protected $_filterRowSource;
    /**
     * Si es una acción de ayuda
     *
     * @var bool
     */
    protected $_isHelper;
    /**
     * El tipo de acción
     *
     * @var int
     */
    protected $_type = 0;
    /**
     * Atributos de datos adicionales
     *
     * @var array
     */
    protected $_data = [];

    /**
     * DataGridActionBase constructor.
     *
     * @param int $id EL id de la acción
     */
    public function __construct($id = null)
    {
        $this->_id = $id;
    }

    /**
     * Devolver el método reflexivo que determina si se muestra la acción
     *
     * @return \ReflectionMethod
     */
    public function getReflectionFilter()
    {
        return $this->_reflectionFilter;
    }

    /**
     * Establecer el método reflexivo que determina si se muestra la acción
     *
     * @param string $class
     * @param string $method
     * @return $this
     * @throws \ReflectionException
     */
    public function setReflectionFilter($class, $method)
    {
        $this->_reflectionFilter = new \ReflectionMethod($class, $method);

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $name string
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
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
     * @return $this
     */
    public function setId($id)
    {
        $this->_id = $id;

        return $this;
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
     * @return $this
     */
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    /**
     * @param $function string
     * @return $this
     */
    public function setOnClickFunction($function)
    {
        $this->_onClickFunction = $function;

        return $this;
    }

    /**
     * @param $args string
     * @return $this
     */
    public function setOnClickArgs($args)
    {
        $this->_onClickArgs[] = $args;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnClick()
    {
        $args = [];

        foreach ($this->_onClickArgs as $arg) {
            $args[] = (!is_numeric($arg) && $arg !== 'this') ? '\'' . $arg . '\'' : $arg;
        }

        return count($args) > 0 ? $this->_onClickFunction . '(' . implode(',', $args) . ')' : $this->_onClickFunction;
    }

    /**
     * @return IconInterface
     */
    public function getIcon()
    {
        return $this->_icon;
    }

    /**
     * @param $icon IconInterface
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->_icon = $icon;

        return $this;
    }

    /**
     * @param $skip bool
     * @return $this
     */
    public function setSkip($skip)
    {
        $this->_isSkip = $skip;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSkip()
    {
        return $this->_isSkip;
    }

    /**
     * @param bool $helper
     * @return $this
     */
    public function setIsHelper($helper)
    {
        $this->_isHelper = $helper;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHelper()
    {
        return $this->_isHelper;
    }

    /**
     * @return string
     */
    public function getFilterRowSource()
    {
        return $this->_filterRowSource;
    }

    /**
     * Filtro para mostrar la acción
     *
     * @param $rowSource string
     * @param mixed $value Valor a filtrar
     * @return $this
     */
    public function setFilterRowSource($rowSource, $value = 1)
    {
        $this->_filterRowSource[] = ['field' => $rowSource, 'value' => $value];

        return $this;
    }

    /**
     * @return int El tipo de acción
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param int $type El tipo de acción definido en DataGridActionType
     * @return $this
     */
    public function setType($type)
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @param array $data Los datos de los atributos
     * @return $this
     */
    public function setData(array $data)
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * Añadir nuevo atributo de datos
     *
     * @param string $name El nombe del atributo
     * @param mixed  $data Los datos del atributo
     * @return $this
     */
    public function addData($name, $data)
    {
        $this->_data[$name] = $data;

        return $this;
    }
}
