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
 * Interface DataGridPagerInterface para la definición del paginador
 *
 * @package SP\Html\DataGrid
 */
interface DataGridPagerInterface
{
    /**
     * Establecer el campo de la búsqueda
     *
     * @param int $sortKey
     */
    public function setSortKey($sortKey);

    /**
     * Devolver el campo de la búsqueda
     *
     * @return int
     */
    public function getSortKey();

    /**
     * Establecer el registro de inicio de la página
     *
     * @param int $limitStart
     *
     * @return static
     */
    public function setLimitStart($limitStart);

    /**
     * Devolver el registro de inicio de la página
     *
     * @return int
     */
    public function getLimitStart();

    /**
     * Establecer el número de registros en una página
     *
     * @param int $limitCount
     *
     * @return static
     */
    public function setLimitCount($limitCount);

    /**
     * Devolver el número de registros en una página
     *
     * @return mixed
     */
    public function getLimitCount();

    /**
     * Establecer el número total de registros obtenidos
     *
     * @param int $totalRows
     */
    public function setTotalRows($totalRows);

    /**
     * Devolver el número total de registros obtenidos
     *
     * @return int
     */
    public function getTotalRows();

    /**
     * Establecer si está activado el filtro
     *
     * @param bool $filterOn
     *
     * @return static
     */
    public function setFilterOn($filterOn);

    /**
     * Devolver si está activado el filtro
     *
     * @return bool
     */
    public function getFilterOn();

    /**
     * Establecer la función javascript para paginar
     *
     * @param string $function
     */
    public function setOnClickFunction($function);

    /**
     * Devolver la función javascript para paginar
     *
     * @return string
     */
    public function getOnClick();

    /**
     * Establecer los argumentos de la función OnClick
     *
     * @param string $args
     */
    public function setOnClickArgs($args);

    /**
     * Devolver la funcion para ir a la primera página
     *
     * @return string
     */
    public function getOnClickFirst();

    /**
     * Devolver la funcion para ir a la última página
     *
     * @return string
     */
    public function getOnClickLast();

    /**
     * Devolver la funcion para ir a la siguiente página
     *
     * @return string
     */
    public function getOnClickNext();

    /**
     * Devolver la funcion para ir a la página anterior
     *
     * @return string
     */
    public function getOnClickPrev();

    /**
     * @return IconInterface
     */
    public function getIconPrev();

    /**
     * @param IconInterface $iconPrev
     */
    public function setIconPrev(IconInterface $iconPrev);

    /**
     * @return IconInterface
     */
    public function getIconNext();

    /**
     * @param IconInterface $iconNext
     */
    public function setIconNext(IconInterface $iconNext);

    /**
     * @return IconInterface
     */
    public function getIconFirst();

    /**
     * @param IconInterface $iconFirst
     */
    public function setIconFirst(IconInterface $iconFirst);

    /**
     * @return IconInterface
     */
    public function getIconLast();

    /**
     * @param IconInterface $iconLast
     */
    public function setIconLast(IconInterface $iconLast);

    /**
     * @param DataGridActionSearch $sourceAction
     */
    public function setSourceAction($sourceAction);

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @param int $sortOrder
     */
    public function setSortOrder($sortOrder);

    /**
     * @return int
     */
    public function getLast();

    /**
     * @return int
     */
    public function getNext();

    /**
     * @return int
     */
    public function getPrev();

    /**
     * @return int
     */
    public function getFirst();

    /**
     * @return DataGridActionSearch
     */
    public function getSourceAction();
}