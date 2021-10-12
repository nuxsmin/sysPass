<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
     * @var \Closure|null
     */
    protected ?Closure $runtimeFilter = null;
    /**
     * The runtime function to pass in the row dato to the action
     *
     * @var \Closure|null
     */
    protected ?Closure $runtimeData = null;
    /**
     * Action's name
     *
     * @var string
     */
    protected string $name = '';
    /**
     * Action's title
     *
     * @var string
     */
    protected string $title = '';
    /**
     * Action's title ID
     *
     * @var string
     */
    protected $id = '';
    /**
     * The JavaScript function to be triggered on OnClick event
     *
     * @var string
     */
    protected string $onClickFunction = '';
    /**
     * The OnClick event arguments
     *
     * @var array|null
     */
    protected ?array $onClickArgs = null;
    /**
     * Action's icon
     *
     * @var IconInterface|null
     */
    protected ?IconInterface $icon = null;
    /**
     * Sets whether this action should be skipped from listing in rows
     *
     * @var bool
     */
    protected bool $isSkip = false;
    /**
     * The row name which determines whether the action is displayed
     *
     * @var array|null
     */
    protected ?array $filterRowSource = null;
    /**
     * Sets as a help action
     *
     * @var bool
     */
    protected ?bool $isHelper = null;
    /**
     * Action's type
     *
     * @var int
     */
    protected int $type = 0;
    /**
     * Data attributes (ie. data-*)
     *
     * @var array|null
     */
    protected ?array $data = null;
    /**
     * Additional attributes (ie. name=*)
     *
     * @var array|null
     */
    protected ?array $attributes = null;
    /**
     * CSS classes
     *
     * @var array|null
     */
    protected ?array $classes = null;
    /**
     * Sets as a selection action, that is, to be displayed on a selection menu
     *
     * @var bool
     */
    protected bool $isSelection = false;

    /**
     * DataGridActionBase constructor.
     *
     * @param int|null $id EL id de la acción
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * Devolver el método reflexivo que determina si se muestra la acción
     *
     * @return callable|null
     */
    public function getRuntimeFilter(): ?callable
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
    public function setRuntimeFilter(string $class, string $method): DataGridActionInterface
    {
        if (method_exists($class, $method)) {
            $this->runtimeFilter = static function ($filter) use ($method) {
//                new \ReflectionMethod($class, $method);
                return $filter->{$method}();
            };
        } else {
            throw new RuntimeException('Method does not exist');
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param $name string
     *
     * @return $this
     */
    public function setName(string $name): DataGridActionInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): DataGridActionBase
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param $title string
     *
     * @return $this
     */
    public function setTitle(string $title): DataGridActionBase
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param $function string
     *
     * @return $this
     */
    public function setOnClickFunction(string $function): DataGridActionBase
    {
        $this->onClickFunction = $function;

        return $this;
    }

    /**
     * @param $args string
     *
     * @return $this
     */
    public function setOnClickArgs(string $args): DataGridActionBase
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
    public function getOnClick(): ?string
    {
        if ($this->onClickArgs !== null) {

            $args = array_map(
                static function ($value) {
                    return (!is_numeric($value) && $value !== 'this') ? '\'' . $value . '\'' : $value;
                },
                $this->onClickArgs
            );

            return count($args) > 0
                ? $this->onClickFunction . '(' . implode(',', $args) . ')'
                : $this->onClickFunction;
        }

        return $this->onClickFunction;

    }

    /**
     * @return IconInterface|null
     */
    public function getIcon(): ?IconInterface
    {
        return $this->icon;
    }

    /**
     * @param $icon IconInterface
     *
     * @return $this
     */
    public function setIcon(IconInterface $icon): DataGridActionBase
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param $skip bool
     *
     * @return $this
     */
    public function setSkip(bool $skip): DataGridActionBase
    {
        $this->isSkip = $skip;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isSkip(): ?bool
    {
        return $this->isSkip;
    }

    /**
     * @return bool|null
     */
    public function isHelper(): ?bool
    {
        return $this->isHelper;
    }

    /**
     * @param bool $helper
     *
     * @return $this
     */
    public function setIsHelper(bool $helper): DataGridActionBase
    {
        $this->isHelper = $helper;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getFilterRowSource(): ?array
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
    public function setFilterRowSource(string $rowSource, $value = 1): DataGridActionBase
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
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int $type El tipo de acción definido en DataGridActionType
     *
     * @return $this
     */
    public function setType(int $type): DataGridActionBase
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data Los datos de los atributos
     *
     * @return $this
     */
    public function setData(array $data): DataGridActionInterface
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
    public function addData(string $name, $data): DataGridActionBase
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
    public function getAttributes(): array
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
    public function setAttributes(array $attributes): DataGridActionInterface
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
    public function addAttribute(string $name, $value): DataGridActionBase
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
     * @return string|null
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
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
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
    public function addClass($value): DataGridActionInterface
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
    public function setIsSelection(bool $isSelection): DataGridActionBase
    {
        $this->isSelection = $isSelection;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getRuntimeData(): ?callable
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
    public function setRuntimeData(callable $function): DataGridActionBase
    {
        $this->runtimeData = $function;

        return $this;
    }
}
