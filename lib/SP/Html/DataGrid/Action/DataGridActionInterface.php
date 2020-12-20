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
    public function setName(string $name);

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $id
     */
    public function setId(string $id);

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @param $title string
     */
    public function setTitle(string $title);

    /**
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * @param $function string
     */
    public function setOnClickFunction(string $function);

    /**
     * @param $args string
     */
    public function setOnClickArgs(string $args);

    /**
     * @return string|null
     */
    public function getOnClick(): ?string;

    /**
     * @param $icon IconInterface
     */
    public function setIcon(IconInterface $icon);

    /**
     * @return IconInterface|null
     */
    public function getIcon(): ?IconInterface;

    /**
     * @param $skip bool
     */
    public function setSkip(bool $skip);

    /**
     * @return bool|null
     */
    public function isSkip(): ?bool;

    /**
     * @param bool $helper
     */
    public function setIsHelper(bool $helper);

    /**
     * @return bool|null
     */
    public function isHelper(): ?bool;

    /**
     * @param string $rowSource
     * @param mixed  $value Valor a filtrar
     */
    public function setFilterRowSource(string $rowSource, $value = 1);

    /**
     * @return array|null
     */
    public function getFilterRowSource(): ?array;

    /**
     * @param int $type El tipo de acción definido en DataGridActionType
     */
    public function setType(int $type);

    /**
     * @return int|null El tipo de acción
     */
    public function getType(): ?int;

    /**
     * @return array
     */
    public function getData(): array;

    /**
     * Establecer atributos de datos
     *
     * @param array $data Los datos de los atributos
     *
     * @return $this
     */
    public function setData(array $data): DataGridActionInterface;

    /**
     * Añadir nuevo atributo de datos
     *
     * @param string $name El nombe del atributo
     * @param mixed  $data Los datos del atributo
     */
    public function addData(string $name, $data);

    /**
     * @return array
     */
    public function getAttributes(): array;

    /**
     * Establecer atributos
     *
     * @param array $attributes Los datos de los atributos
     *
     * @return $this
     */
    public function setAttributes(array $attributes): DataGridActionInterface;

    /**
     * Añadir nuevo atributo
     *
     * @param string $name El nombe del atributo
     * @param mixed  $value
     */
    public function addAttribute(string $name, $value);

    /**
     * Devolver el método reflexivo que determina si se muestra la acción
     *
     * @return callable|null
     */
    public function getRuntimeFilter(): ?callable;

    /**
     * Establecer el método reflexivo que determina si se muestra la acción
     *
     * @param string $class
     * @param string $method
     *
     * @return $this
     */
    public function setRuntimeFilter(string $class, string $method): DataGridActionInterface;

    /**
     * Returns classes as a string
     *
     * @return string|null
     */
    public function getClassesAsString(): ?string;

    /**
     * Returns classes
     *
     * @return array
     */
    public function getClasses(): array;

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
    public function addClass($value): DataGridActionInterface;

    /**
     * Returns if the action is used for selecting multiple items
     *
     * @return bool|null
     */
    public function isSelection(): bool;

    /**
     * Returns the runtime function to pass in the row dato to the action
     *
     * @return callable|null
     */
    public function getRuntimeData(): ?callable;
}