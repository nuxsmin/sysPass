<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Html\DataGrid\Action;

use SP\Html\Assets\IconInterface;

/**
 * Interface DataGridActionInterface
 *
 * @package SP\Html\DataGrid
 */
interface DataGridActionInterface
{
    public function setName(string $name);

    public function getName(): ?string;

    public function setId(int $id);

    public function getId(): string;

    public function setTitle(string $title);

    public function getTitle(): ?string;

    public function setOnClickFunction(string $function);

    public function setOnClickArgs(string $args);

    public function getOnClick(): ?string;

    public function setIcon(IconInterface $icon);

    public function getIcon(): ?IconInterface;

    public function setSkip(bool $skip);

    public function isSkip(): ?bool;

    public function setIsHelper(bool $helper);

    public function isHelper(): ?bool;

    public function setFilterRowSource(string $rowSource, mixed $value = 1);

    public function getFilterRowSource(): ?array;

    public function setType(int $type);

    public function getType(): ?int;

    public function getData(): ?array;

    public function setData(array $data): DataGridActionInterface;

    public function addData(string $name, mixed $data);

    public function getAttributes(): array;

    public function setAttributes(array $attributes): DataGridActionInterface;

    public function addAttribute(string $name, mixed $value);

    public function getRuntimeFilter(): ?callable;

    /**
     * Establecer el método reflexivo que determina si se muestra la acción
     */
    public function setRuntimeFilter(string $class, string $method): DataGridActionInterface;

    public function getClassesAsString(): ?string;

    public function getClasses(): array;

    public function setClasses(array $classes);

    public function addClass(mixed $value): DataGridActionInterface;

    /**
     * Returns if the action is used for selecting multiple items
     */
    public function isSelection(): bool;

    /**
     * Returns the runtime function to pass in the row dato to the action
     */
    public function getRuntimeData(): ?callable;
}
