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

use SP\Html\Assets\IconInterface;

defined('APP_ROOT') || die();

/**
 * Interface DataGridActionInterface
 *
 * @package SP\Html\DataGrid
 */
interface DataGridActionInterface
{
    /**
     * @param $name string
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param int $id
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param $title string
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param $function string
     */
    public function setOnClickFunction($function);

    /**
     * @param $args string
     */
    public function setOnClickArgs($args);

    /**
     * @return string
     */
    public function getOnClick();

    /**
     * @param $icon IconInterface
     */
    public function setIcon($icon);

    /**
     * @return IconInterface
     */
    public function getIcon();

    /**
     * @param $skip bool
     */
    public function setSkip($skip);

    /**
     * @return bool
     */
    public function isSkip();

    /**
     * @param bool $helper
     */
    public function setIsHelper($helper);

    /**
     * @return bool
     */
    public function isHelper();

    /**
     * @param string $rowSource
     * @param mixed  $value Valor a filtrar
     */
    public function setFilterRowSource($rowSource, $value = 1);

    /**
     * @return array
     */
    public function getFilterRowSource();

    /**
     * @param int $type El tipo de acción definido en DataGridActionType
     */
    public function setType($type);

    /**
     * @return int El tipo de acción
     */
    public function getType();

    /**
     * @return array
     */
    public function getData();

    /**
     * Establecer atributos de datos
     *
     * @param array $data Los datos de los atributos
     *
     * @return $this
     */
    public function setData(array $data);

    /**
     * Añadir nuevo atributo de datos
     *
     * @param string $name El nombe del atributo
     * @param mixed  $data Los datos del atributo
     */
    public function addData($name, $data);

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * Establecer atributos
     *
     * @param array $attributes Los datos de los atributos
     *
     * @return $this
     */
    public function setAttributes(array $attributes);

    /**
     * Añadir nuevo atributo
     *
     * @param string $name El nombe del atributo
     * @param mixed  $value
     */
    public function addAttribute($name, $value);

    /**
     * Devolver el método reflexivo que determina si se muestra la acción
     *
     * @return callable
     */
    public function getRuntimeFilter();

    /**
     * Establecer el método reflexivo que determina si se muestra la acción
     *
     * @param string $class
     * @param string $method
     *
     * @return $this
     */
    public function setRuntimeFilter($class, $method);

    /**
     * Returns classes as a string
     *
     * @return string
     */
    public function getClassesAsString();

    /**
     * Returns classes
     *
     * @return array
     */
    public function getClasses();

    /**
     * Set classes
     *
     * @param array $classes
     */
    public function setClasses(array $classes);

    /**
     * Adds a new class
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function addClass($value);

    /**
     * Returns if the action is used for selecting multiple items
     *
     * @return bool
     */
    public function isSelection(): bool;

    /**
     * Returns the runtime function to pass in the row dato to the action
     *
     * @return callable
     */
    public function getRuntimeData();
}