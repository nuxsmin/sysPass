<?php
declare(strict_types=1);
/**
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

use Closure;
use RuntimeException;
use SP\Html\Assets\IconInterface;

/**
 * Class DataGridAction para crear una acción para cada elemento de la matriz de datos
 */
abstract class DataGridActionBase implements DataGridActionInterface
{
    /**
     * The runtime function that determines if the action should be displayed
     */
    protected ?Closure $runtimeFilter = null;
    /**
     * The runtime function to pass in the row dato to the action
     */
    protected ?Closure $runtimeData = null;
    /**
     * Action's name
     */
    protected ?string $name = null;
    /**
     * Action's title
     */
    protected ?string $title = null;
    /**
     * Action's title ID
     */
    protected ?int $id = null;
    /**
     * The JavaScript function to be triggered on OnClick event
     */
    protected string $onClickFunction = '';
    /**
     * The OnClick event arguments
     */
    protected ?array $onClickArgs = null;
    /**
     * Action's icon
     */
    protected ?IconInterface $icon = null;
    /**
     * Sets whether this action should be skipped from listing in rows
     */
    protected bool $isSkip = false;
    /**
     * The row name which determines whether the action is displayed
     */
    protected ?array $filterRowSource = null;
    /**
     * Sets as a help action
     */
    protected ?bool $isHelper = null;
    /**
     * Action's type
     */
    protected ?int $type = null;
    /**
     * Data attributes (ie. data-*)
     */
    protected ?array $data = null;
    /**
     * Additional attributes (ie. name=*)
     */
    protected ?array $attributes = null;
    /**
     * CSS classes
     */
    protected ?array $classes = null;
    /**
     * Sets as a selection action, that is, to be displayed on a selection menu
     */
    protected bool $isSelection = false;

    /**
     * DataGridActionBase constructor.
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * Devolver el método reflexivo que determina si se muestra la acción
     */
    public function getRuntimeFilter(): ?callable
    {
        return $this->runtimeFilter;
    }

    /**
     * Establecer el método reflexivo que determina si se muestra la acción
     *
     * @throws RuntimeException
     */
    public function setRuntimeFilter(string $class, string $method): DataGridActionInterface
    {
        if (method_exists($class, $method)) {
            $this->runtimeFilter = static function ($filter) use ($method) {
                return $filter->{$method}();
            };
        } else {
            throw new RuntimeException('Method does not exist');
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): DataGridActionInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getId(): string
    {
        return (string)$this->id;
    }

    public function setId(int $id): DataGridActionBase
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): DataGridActionBase
    {
        $this->title = $title;

        return $this;
    }

    public function setOnClickFunction(string $function): DataGridActionBase
    {
        $this->onClickFunction = $function;

        return $this;
    }

    public function setOnClickArgs(string $args): DataGridActionBase
    {
        if ($this->onClickArgs === null) {
            $this->onClickArgs = [];
        }

        $this->onClickArgs[] = $args;

        return $this;
    }

    public function getOnClick(): ?string
    {
        if ($this->onClickArgs !== null) {
            $args = array_map(
                static fn($value) => (!is_numeric($value) && $value !== 'this') ? sprintf('\'%s\'', $value) : $value,
                $this->onClickArgs
            );

            return count($args) > 0
                ? sprintf('%s(%s)', $this->onClickFunction, implode(',', $args))
                : $this->onClickFunction;
        }

        return $this->onClickFunction;
    }

    public function getIcon(): ?IconInterface
    {
        return $this->icon;
    }

    public function setIcon(IconInterface $icon): DataGridActionBase
    {
        $this->icon = $icon;

        return $this;
    }

    public function setSkip(bool $skip): DataGridActionBase
    {
        $this->isSkip = $skip;

        return $this;
    }

    public function isSkip(): ?bool
    {
        return $this->isSkip;
    }

    public function isHelper(): ?bool
    {
        return $this->isHelper;
    }

    public function setIsHelper(bool $helper): DataGridActionBase
    {
        $this->isHelper = $helper;

        return $this;
    }

    public function getFilterRowSource(): ?array
    {
        return $this->filterRowSource;
    }

    /**
     * Filtro para mostrar la acción
     *
     * @return $this
     */
    public function setFilterRowSource(string $rowSource, mixed $value = 1): DataGridActionBase
    {
        if ($this->filterRowSource === null) {
            $this->filterRowSource = [];
        }

        $this->filterRowSource[] = ['field' => $rowSource, 'value' => $value];

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): DataGridActionBase
    {
        $this->type = $type;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): DataGridActionInterface
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Añadir nuevo atributo de datos
     */
    public function addData(string $name, mixed $data): DataGridActionBase
    {
        if ($this->data === null) {
            $this->data = [];
        }

        $this->data[$name] = $data;

        return $this;
    }

    public function getAttributes(): array
    {
        return (array)$this->attributes;
    }

    /**
     * Establecer atributos
     */
    public function setAttributes(array $attributes): DataGridActionInterface
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Añadir nuevo atributo
     */
    public function addAttribute(string $name, mixed $value): DataGridActionBase
    {
        if ($this->attributes === null) {
            $this->attributes = [];
        }

        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Returns classes as a string
     */
    public function getClassesAsString(): ?string
    {
        if ($this->classes === null) {
            return '';
        }

        return implode(' ', $this->classes);
    }

    /**
     * Returns classes
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Set classes
     */
    public function setClasses(array $classes): void
    {
        $this->classes = $classes;
    }

    /**
     * Adds a new class
     */
    public function addClass(mixed $value): DataGridActionInterface
    {
        if ($this->classes === null) {
            $this->classes = [];
        }

        $this->classes[] = $value;

        return $this;
    }

    /**
     * Returns if the action is used for selecting multiple items
     */
    public function isSelection(): bool
    {
        return $this->isSelection;
    }

    public function setIsSelection(bool $isSelection): DataGridActionBase
    {
        $this->isSelection = $isSelection;

        return $this;
    }

    public function getRuntimeData(): ?callable
    {
        return $this->runtimeData;
    }

    /**
     * Sets the runtime data function
     */
    public function setRuntimeData(callable $function): DataGridActionBase
    {
        $this->runtimeData = $function;

        return $this;
    }
}
