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
    protected $sortKey = 0;
    /**
     * @var int
     */
    protected $sortOrder = 0;
    /**
     * @var int
     */
    protected $limitStart = 0;
    /**
     * @var int
     */
    protected $limitCount = 0;
    /**
     * @var int
     */
    protected $totalRows = 0;
    /**
     * @var bool
     */
    protected $filterOn = false;
    /**
     * @var string
     */
    protected $onClickFunction = '';
    /**
     * @var array
     */
    protected $onClickArgs = [];
    /**
     * @var IconInterface
     */
    protected $iconPrev;
    /**
     * @var IconInterface
     */
    protected $iconNext;
    /**
     * @var IconInterface
     */
    protected $iconFirst;
    /**
     * @var IconInterface
     */
    protected $iconLast;
    /**
     * @var DataGridActionSearch
     */
    protected $sourceAction;
    /**
     * @var string
     */
    protected $sk;

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     *
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @param string $sk
     *
     * @return $this
     */
    public function setSk($sk)
    {
        $this->sk = $sk;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconPrev()
    {
        return $this->iconPrev;
    }

    /**
     * @param IconInterface $iconPrev
     *
     * @return $this
     */
    public function setIconPrev(IconInterface $iconPrev)
    {
        $this->iconPrev = $iconPrev;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconNext()
    {
        return $this->iconNext;
    }

    /**
     * @param IconInterface $iconNext
     *
     * @return $this
     */
    public function setIconNext(IconInterface $iconNext)
    {
        $this->iconNext = $iconNext;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconFirst()
    {
        return $this->iconFirst;
    }

    /**
     * @param IconInterface $iconFirst
     *
     * @return $this
     */
    public function setIconFirst(IconInterface $iconFirst)
    {
        $this->iconFirst = $iconFirst;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconLast()
    {
        return $this->iconLast;
    }

    /**
     * @param IconInterface $iconLast
     *
     * @return $this
     */
    public function setIconLast(IconInterface $iconLast)
    {
        $this->iconLast = $iconLast;

        return $this;
    }

    /**
     * Devolver el campo de la búsqueda
     *
     * @return int
     */
    public function getSortKey()
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
    public function setSortKey($sortKey)
    {
        $this->sortKey = $sortKey;

        return $this;
    }

    /**
     * Devolver el registro de inicio de la página
     *
     * @return int
     */
    public function getLimitStart()
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
    public function setLimitStart($limitStart)
    {
        $this->limitStart = $limitStart;

        return $this;
    }

    /**
     * Devolver el número de registros en una página
     *
     * @return mixed
     */
    public function getLimitCount()
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
    public function setLimitCount($limitCount)
    {
        $this->limitCount = $limitCount;

        return $this;
    }

    /**
     * Devolver el número de página inicial
     *
     * @return int
     */
    public function getFirstPage()
    {
        return ceil(($this->limitStart + 1) / $this->limitCount);
    }

    /**
     * Devolver el número de página final
     *
     * @return int
     */
    public function getLastPage()
    {
        return ceil($this->totalRows / $this->limitCount);
    }

    /**
     * Devolver el número total de registros obtenidos
     *
     * @return int
     */
    public function getTotalRows()
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
    public function setTotalRows($totalRows)
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    /**
     * Devolver si está activado el filtro
     *
     * @return bool
     */
    public function getFilterOn()
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
    public function setFilterOn($filterOn)
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
    public function setOnClickFunction($function)
    {
        $this->onClickFunction = $function;

        return $this;
    }

    /**
     * Devolver la función javascript para paginar
     *
     * @return string
     */
    public function getOnClick()
    {
        $args = $this->parseArgs();

        return count($args) > 0 ? $this->onClickFunction . '(' . implode(',', $args) . ')' : $this->onClickFunction;
    }

    /**
     * @return array
     */
    protected function parseArgs()
    {
        $args = array();

        foreach ($this->onClickArgs as $arg) {
            $args[] = (!is_numeric($arg) && $arg !== 'this') ? '\'' . $arg . '\'' : $arg;
        }

        return $args;
    }

    /**
     * Establecer los argumentos de la función OnClick
     *
     * @param mixed $args
     *
     * @return $this
     */
    public function setOnClickArgs($args)
    {
        $this->onClickArgs[] = $args;

        return $this;
    }

    /**
     * Devolver la funcion para ir a la primera página
     *
     * @return string
     */
    public function getOnClickFirst()
    {
        $args = $this->parseArgs();
        $args[] = $this->getFirst();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return int
     */
    public function getFirst()
    {
        return 0;
    }

    /**
     * Devolver la funcion para ir a la última página
     *
     * @return string
     */
    public function getOnClickLast()
    {
        $args = $this->parseArgs();
        $args[] = $this->getLast();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return float|int
     */
    public function getLast()
    {
        return (($this->totalRows % $this->limitCount) == 0) ? $this->totalRows - $this->limitCount : floor($this->totalRows / $this->limitCount) * $this->limitCount;
    }

    /**
     * Devolver la funcion para ir a la siguiente página
     *
     * @return string
     */
    public function getOnClickNext()
    {
        $args = $this->parseArgs();
        $args[] = $this->getNext();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return int
     */
    public function getNext()
    {
        return ($this->limitStart + $this->limitCount);
    }

    /**
     * Devolver la funcion para ir a la página anterior
     *
     * @return string
     */
    public function getOnClickPrev()
    {
        $args = $this->parseArgs();
        $args[] = $this->getPrev();

        return $this->onClickFunction . '(' . implode(',', $args) . ')';
    }

    /**
     * @return int
     */
    public function getPrev()
    {
        return ($this->limitStart - $this->limitCount);
    }

    /**
     * @return DataGridActionSearch
     */
    public function getSourceAction()
    {
        return $this->sourceAction;
    }

    /**
     * @param DataGridActionSearch $sourceAction
     *
     * @return $this
     */
    public function setSourceAction($sourceAction)
    {
        $this->sourceAction = $sourceAction;

        return $this;
    }
}