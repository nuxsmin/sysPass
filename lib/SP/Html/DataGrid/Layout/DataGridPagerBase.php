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

namespace SP\Html\DataGrid\Layout;

use SP\Html\Assets\IconInterface;
use SP\Html\DataGrid\Action\DataGridActionSearch;

/**
 * Class DataGridPagerBase para implementar los métodos del paginador
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridPagerBase implements DataGridPagerInterface
{
    protected int           $sortKey         = 0;
    protected int           $sortOrder       = 0;
    protected int           $limitStart      = 0;
    protected int           $limitCount      = 0;
    protected int           $totalRows       = 0;
    protected bool          $filterOn        = false;
    protected string        $onClickFunction = '';
    protected array         $onClickArgs     = [];
    protected IconInterface $iconPrev;
    protected IconInterface $iconNext;
    protected IconInterface $iconFirst;
    protected IconInterface $iconLast;
    protected DataGridActionSearch $sourceAction;
    protected string        $sk;

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): DataGridPagerBase
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getIconPrev(): IconInterface
    {
        return $this->iconPrev;
    }

    public function setIconPrev(IconInterface $iconPrev): DataGridPagerBase
    {
        $this->iconPrev = $iconPrev;

        return $this;
    }

    public function getIconNext(): IconInterface
    {
        return $this->iconNext;
    }

    public function setIconNext(IconInterface $iconNext): DataGridPagerBase
    {
        $this->iconNext = $iconNext;

        return $this;
    }

    public function getIconFirst(): IconInterface
    {
        return $this->iconFirst;
    }

    public function setIconFirst(IconInterface $iconFirst): DataGridPagerBase
    {
        $this->iconFirst = $iconFirst;

        return $this;
    }

    public function getIconLast(): IconInterface
    {
        return $this->iconLast;
    }

    public function setIconLast(IconInterface $iconLast): DataGridPagerBase
    {
        $this->iconLast = $iconLast;

        return $this;
    }

    public function getSortKey(): int
    {
        return $this->sortKey;
    }

    /**
     * Establecer el campo de la búsqueda
     */
    public function setSortKey(int $sortKey): DataGridPagerBase
    {
        $this->sortKey = $sortKey;

        return $this;
    }

    /**
     * Devolver el registro de inicio de la página
     */
    public function getLimitStart(): int
    {
        return $this->limitStart;
    }

    /**
     * Establecer el registro de inicio de la página
     */
    public function setLimitStart(int $limitStart): DataGridPagerBase
    {
        $this->limitStart = $limitStart;

        return $this;
    }

    /**
     * Devolver el número de registros en una página
     */
    public function getLimitCount(): int
    {
        return $this->limitCount;
    }

    /**
     * Establecer el número de registros en una página
     */
    public function setLimitCount(int $limitCount): DataGridPagerBase
    {
        $this->limitCount = $limitCount;

        return $this;
    }

    /**
     * Devolver el número de página inicial
     */
    public function getFirstPage(): int
    {
        return $this->limitCount > 0 ? (int)ceil(($this->limitStart + 1) / $this->limitCount) : 1;
    }

    /**
     * Devolver el número de página final
     */
    public function getLastPage(): int
    {
        return $this->limitCount > 0 ? (int)ceil($this->totalRows / $this->limitCount) : 1;
    }

    /**
     * Devolver el número total de registros obtenidos
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * Establecer el número total de registros obtenidos
     */
    public function setTotalRows(int $totalRows): DataGridPagerBase
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    /**
     * Devolver si está activado el filtro
     */
    public function getFilterOn(): bool
    {
        return $this->filterOn;
    }

    /**
     * Establecer si está activado el filtro
     */
    public function setFilterOn(bool $filterOn): DataGridPagerBase
    {
        $this->filterOn = $filterOn;

        return $this;
    }

    /**
     * Establecer la función javascript para paginar
     */
    public function setOnClickFunction(string $function): DataGridPagerBase
    {
        $this->onClickFunction = $function;

        return $this;
    }

    /**
     * Devolver la función javascript para paginar
     */
    public function getOnClick(): string
    {
        $args = $this->parseArgs();

        return count($args) > 0
            ? $this->onClickFunction . '(' . implode(',', $args) . ')'
            : $this->onClickFunction;
    }

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
     */
    public function setOnClickArgs(string $args): DataGridPagerBase
    {
        $this->onClickArgs[] = $args;

        return $this;
    }

    /**
     * Devolver la funcion para ir a la primera página
     */
    public function getOnClickFirst(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getFirst();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    public function getFirst(): int
    {
        return 0;
    }

    /**
     * Devolver la funcion para ir a la última página
     */
    public function getOnClickLast(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getLast();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    public function getLast(): int
    {
        return (($this->totalRows % $this->limitCount) === 0)
            ? $this->totalRows - $this->limitCount
            : floor($this->totalRows / $this->limitCount) * $this->limitCount;
    }

    /**
     * Devolver la funcion para ir a la siguiente página
     */
    public function getOnClickNext(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getNext();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    public function getNext(): int
    {
        return ($this->limitStart + $this->limitCount);
    }

    /**
     * Devolver la funcion para ir a la página anterior
     */
    public function getOnClickPrev(): string
    {
        $args = $this->parseArgs();
        $args[] = $this->getPrev();

        return sprintf('%s(%s)', $this->onClickFunction, implode(',', $args));
    }

    public function getPrev(): int
    {
        return ($this->limitStart - $this->limitCount);
    }

    public function getSourceAction(): DataGridActionSearch
    {
        return $this->sourceAction;
    }

    public function setSourceAction(DataGridActionSearch $sourceAction): DataGridPagerBase
    {
        $this->sourceAction = $sourceAction;

        return $this;
    }
}
