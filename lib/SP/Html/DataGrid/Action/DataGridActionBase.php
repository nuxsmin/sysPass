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

namespace SP\Html\DataGrid\Action;

use RuntimeException;
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
     * The runtime function that determines if the action should be displayed
     *
     * @var callable
     */
    protected $runtimeFilter;
    /**
     * The runtime function to pass in the row dato to the action
     *
     * @var callable
     */
    protected $runtimeData;
    /**
     * Action's name
     *
     * @var string
     */
    protected $name = '';
    /**
     * Action's title
     *
     * @var string
     */
    protected $title = '';
    /**
     * Action's title ID
     *
     * @var int
     */
    protected $id = 0;
    /**
     * The JavaScript function to be triggered on OnClick event
     *
     * @var string
     */
    protected $onClickFunction = '';
    /**
     * The OnClick event arguments
     *
     * @var array
     */
    protected $onClickArgs;
    /**
     * Action's icon
     *
     * @var IconInterface
     */
    protected $icon;
    /**
     * Sets whether this action should be skipped from listing in rows
     *
     * @var bool
     */
    protected $isSkip = false;
    /**
     * The row name which determines whether the action is displayed
     *
     * @var array
     */
    protected $filterRowSource;
    /**
     * Sets as a help action
     *
     * @var bool
     */
    protected $isHelper;
    /**
     * Action's type
     *
     * @var int
     */
    protected $type = 0;
    /**
     * Data attributes (ie. data-*)
     *
     * @var array
     */
    protected $data;
    /**
     * Additional attributes (ie. name=*)
     *
     * @var array
     */
    protected $attributes;
    /**
     * CSS classes
     *
     * @var array
     */
    protected $classes;
    /**
     * Sets as a selection action, that is, to be displayed on a selection menu
     *
     * @var bool
     */
    protected $isSelection = false;

    /**
     * DataGridActionBase constructor.
     *
     * @param int $id EL id de la acción
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * Devolver el método reflexivo que determina si se muestra la acción
     *
     * @return callable
     */
    public function getRuntimeFilter()
    {
        return $this->runtimeFilter;
    }

    /**
     * Establecer el método reflexivo que determina si se muestra la acción
     *
     * @param string $class
     * @param string $method
     *
     * @return $this
     * @throws RuntimeException
     */
    public function setRuntimeFilter($class, $method)
    {
        if (method_exists($class, $method)) {
            $this->runtimeFilter = function ($filter) use ($method) {
//                new \ReflectionMethod($class, $method);
                return $filter->{$method}();
            };
        } else {
            throw new RuntimeException('Method does not exist');
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name string
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title string
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param $function string
     *
     * @return $this
     */
    public function setOnClickFunction($function)
    {
        $this->onClickFunction = $function;

        return $this;
    }

    /**
     * @param $args string
     *
     * @return $this
     */
    public function setOnClickArgs($args)
    {
        if ($this->onClickArgs === null) {
            $this->onClickArgs = [];
        }

        $this->onClickArgs[] = $args;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnClick()
    {
        if ($this->onClickArgs !== null) {

            $args = array_map(function ($value) {
                return (!is_numeric($value) && $value !== 'this') ? '\'' . $value . '\'' : $value;
            }, $this->onClickArgs);

            return count($args) > 0 ? $this->onClickFunction . '(' . implode(',', $args) . ')' : $this->onClickFunction;
        }

        return $this->onClickFunction;

    }

    /**
     * @return IconInterface
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param $icon IconInterface
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param $skip bool
     *
     * @return $this
     */
    public function setSkip($skip)
    {
        $this->isSkip = $skip;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSkip()
    {
        return $this->isSkip;
    }

    /**
     * @return bool
     */
    public function isHelper()
    {
        return $this->isHelper;
    }

    /**
     * @param bool $helper
     *
     * @return $this
     */
    public function setIsHelper($helper)
    {
        $this->isHelper = $helper;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilterRowSource()
    {
        return $this->filterRowSource;
    }

    /**
     * Filtro para mostrar la acción
     *
     * @param       $rowSource string
     * @param mixed $value     Valor a filtrar
     *
     * @return $this
     */
    public function setFilterRowSource($rowSource, $value = 1)
    {
        if ($this->filterRowSource === null) {
            $this->filterRowSource = [];
        }

        $this->filterRowSource[] = ['field' => $rowSource, 'value' => $value];

        return $this;
    }

    /**
     * @return int El tipo de acción
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type El tipo de acción definido en DataGridActionType
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return (array)$this->data;
    }

    /**
     * @param array $data Los datos de los atributos
     *
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Añadir nuevo atributo de datos
     *
     * @param string $name El nombe del atributo
     * @param mixed  $data Los datos del atributo
     *
     * @return $this
     */
    public function addData($name, $data)
    {
        if ($this->data === null) {
            $this->data = [];
        }

        $this->data[$name] = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return (array)$this->attributes;
    }

    /**
     * Establecer atributos
     *
     * @param array $attributes Los datos de los atributos
     *
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Añadir nuevo atributo
     *
     * @param string $name El nombe del atributo
     * @param mixed  $value
     *
     * @return $this
     */
    public function addAttribute($name, $value)
    {
        if ($this->attributes === null) {
            $this->attributes = [];
        }

        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Returns classes as a string
     *
     * @return string
     */
    public function getClassesAsString()
    {
        if ($this->classes === null) {
            return '';
        }

        return implode(' ', $this->classes);
    }

    /**
     * Returns classes
     *
     * @return array
     */
    public function getClasses()
    {
        return (array)$this->classes;
    }

    /**
     * Set classes
     *
     * @param array $classes
     */
    public function setClasses(array $classes)
    {
        $this->classes = $classes;
    }

    /**
     * Adds a new class
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function addClass($value)
    {
        if ($this->classes === null) {
            $this->classes = [];
        }

        $this->classes[] = $value;

        return $this;
    }

    /**
     * Returns if the action is used for selecting multiple items
     *
     * @return bool
     */
    public function isSelection(): bool
    {
        return $this->isSelection;
    }

    /**
     * @param bool $isSelection
     *
     * @return DataGridActionBase
     */
    public function setIsSelection(bool $isSelection)
    {
        $this->isSelection = $isSelection;

        return $this;
    }

    /**
     * @return callable
     */
    public function getRuntimeData()
    {
        return $this->runtimeData;
    }

    /**
     * Sets the runtime data function
     *
     * @param callable $function
     *
     * @return $this
     */
    public function setRuntimeData(callable $function)
    {
        $this->runtimeData = $function;

        return $this;
    }
}
