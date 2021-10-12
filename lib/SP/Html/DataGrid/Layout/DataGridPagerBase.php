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

namespace SP\Html\DataGrid\Layout;

use SP\Html\Assets\IconInterface;
use SP\Html\DataGrid\Action\DataGridActionSearch;

defined('APP_ROOT') || die();

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
    protected int $sortKey = 0;
    /**
     * @var int
     */
    protected int $sortOrder = 0;
    /**
     * @var int
     */
    protected int $limitStart = 0;
    /**
     * @var int
     */
    protected int $limitCount = 0;
    /**
     * @var int
     */
    protected int $totalRows = 0;
    /**
     * @var bool
     */
    protected bool $filterOn = false;
    /**
     * @var string
     */
    protected string $onClickFunction = '';
    /**
     * @var array
     */
    protected array $onClickArgs = [];
    /**
     * @var IconInterface
     */
    protected IconInterface $iconPrev;
    /**
     * @var IconInterface
     */
    protected IconInterface $iconNext;
    /**
     * @var IconInterface
     */
    protected IconInterface $iconFirst;
    /**
     * @var IconInterface
     */
    protected IconInterface $iconLast;
    /**
     * @var DataGridActionSearch
     */
    protected DataGridActionSearch $sourceAction;
    /**
     * @var string
     */
    protected string $sk;

    /**
     * @return int
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     *
     * @return $this
     */
    public function setSortOrder(int $sortOrder): DataGridPagerBase
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconPrev(): IconInterface
    {
        return $this->iconPrev;
    }

    /**
     * @param IconInterface $iconPrev
     *
     * @return $this
     */
    public function setIconPrev(IconInterface $iconPrev): DataGridPagerBase
    {
        $this->iconPrev = $iconPrev;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconNext(): IconInterface
    {
        return $this->iconNext;
    }

    /**
     * @param IconInterface $iconNext
     *
     * @return $this
     */
    public function setIconNext(IconInterface $iconNext): DataGridPagerBase
    {
        $this->iconNext = $iconNext;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconFirst(): IconInterface
    {
        return $this->iconFirst;
    }

    /**
     * @param IconInterface $iconFirst
     *
     * @return $this
     */
    public function setIconFirst(IconInterface $iconFirst): DataGridPagerBase
    {
        $this->iconFirst = $iconFirst;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconLast(): IconInterface
    {
        return $this->iconLast;
    }

    /**
     * @param IconInterface $iconLast
     *
     * @return $this
     */
    public function setIconLast(IconInterface $iconLast): DataGridPagerBase
    {
        $this->iconLast = $iconLast;

        return $this;
    }

    /**
     * Devolver el campo de la búsqueda
     *
     * @return int
     */
    public function getSortKey(): int
    {
        return $this->sortKey;
    }

    /**
     * Establecer el campo de la búsqueda
     *
     * @param int $sortKey
     *
     * @return $this
     */
    public function setSortKey(int $sortKey): DataGridPagerBase
    {
        $this->sortKey = $sortKey;

        return $this;
    }

    /**
     * Devolver el registro de inicio de la página
     *
     * @return int
     */
    public function getLimitStart(): int
    {
        return $this->limitStart;
    }

    /**
     * Establecer el registro de inicio de la página
     *
     * @param int $limitStart
     *
     * @return $this
     */
    public function setLimitStart(int $limitStart): DataGridPagerBase
    {
        $this->limitStart = $limitStart;

        return $this;
    }

    /**
     * Devolver el número de registros en una página
     *
     * @return int
     */
    public function getLimitCount(): int
    {
        return $this->limitCount;
    }

    /**
     * Establecer el número de registros en una página
     *
     * @param int $limitCount
     *
     * @return $this
     */
    public function setLimitCount(int $limitCount): DataGridPagerBase
    {
        $this->limitCount = $limitCount;

        return $this;
    }

    /**
     * Devolver el número de página inicial
     *
     * @return int
     */
    public function getFirstPage(): int
    {
        return ceil(($this->limitStart + 1) / $this->limitCount);
    }

    /**
     * Devolver el número de página final
     *
     * @return int
     */
    public function getLastPage(): int
    {
        return ceil($this->totalRows / $this->limitCount);
    }

    /**
     * Devolver el número total de registros obtenidos
     *
     * @return int
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * Establecer el número total de registros obtenidos
     *
     * @param int $totalRows
     *
     * @return $this
     */
    public function setTotalRows(int $totalRows): DataGridPagerBase
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    /**
     * Devolver si está activado el filtro
     *
     * @return bool
     */
    public function getFilterOn(): bool
    {
        return $this->filterOn;
    }

    /**
     * Establecer si está activado el filtro
     *
     * @param bool $filterOn
     *
     * @return $this
     */
    public function setFilterOn(bool $filterOn): DataGridPagerBase
    {
        $this->filterOn = $filterOn;

        return $this;
    }

    /**
     * Establecer la función javascript para paginar
     *
     * @param string $function
     *
     * @return $this
     */
    public function setOnClickFunction(string $function): DataGridPagerBase
    {
        $this->onClickFunction = $function;

        return $this;
    }

    /**
     * Devolver la función javascript para paginar
     *
     * @return string
     */
    public function getOnClick(): string
    {
        $args = $this->parseArgs();

        return count($args) > 0
            ? $this->onClickFunction . '(' . implode(',', $args) . ')'
            : $this->onClickFunction;
    }

    /**
     * @return array
     */
    protected function parseArgs(): array
    {
        $args = array();

        foreach ($this->onClickArgs as $arg) {
            $args[] = (!is_numeric($arg) && $arg !== 'this')
                ? '\'' . $arg . '\''
                : $arg;
        }

        return $args;
    }

    /**
     * Establecer los argumentos de la función OnClick
     *
     * @param string $args
     *
     * @return $this
     */
    public function setOnClickArgs(string $args): DataGridPagerBase
    {
        $this->onClickArgs[] = $args;

        return $this;
    }

    /**
     * Devolver la funcion para ir a la primera página
     *
     * @return string
     */
    public function getOnClickFirst(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getFirst();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return int
     */
    public function getFirst(): int
    {
        return 0;
    }

    /**
     * Devolver la funcion para ir a la última página
     *
     * @return string
     */
    public function getOnClickLast(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getLast();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return float|int
     */
    public function getLast(): int
    {
        return (($this->totalRows % $this->limitCount) === 0)
            ? $this->totalRows - $this->limitCount
            : floor($this->totalRows / $this->limitCount) * $this->limitCount;
    }

    /**
     * Devolver la funcion para ir a la siguiente página
     *
     * @return string
     */
    public function getOnClickNext(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getNext();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return int
     */
    public function getNext(): int
    {
        return ($this->limitStart + $this->limitCount);
    }

    /**
     * Devolver la funcion para ir a la página anterior
     *
     * @return string
     */
    public function getOnClickPrev(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getPrev();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return int
     */
    public function getPrev(): int
    {
        return ($this->limitStart - $this->limitCount);
    }

    /**
     * @return DataGridActionSearch
     */
    public function getSourceAction(): DataGridActionSearch
    {
        return $this->sourceAction;
    }

    /**
     * @param DataGridActionSearch $sourceAction
     *
     * @return $this
     */
    public function setSourceAction(DataGridActionSearch $sourceAction): DataGridPagerBase
    {
        $this->sourceAction = $sourceAction;

        return $this;
    }
}