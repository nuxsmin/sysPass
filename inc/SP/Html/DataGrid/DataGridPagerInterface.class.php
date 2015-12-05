<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Html\DataGrid;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
     * @return DataGridIcon
     */
    public function getIconPrev();

    /**
     * @param DataGridIcon $iconPrev
     */
    public function setIconPrev($iconPrev);

    /**
     * @return DataGridIcon
     */
    public function getIconNext();

    /**
     * @param DataGridIcon $iconNext
     */
    public function setIconNext($iconNext);

    /**
     * @return DataGridIcon
     */
    public function getIconFirst();

    /**
     * @param DataGridIcon $iconFirst
     */
    public function setIconFirst($iconFirst);

    /**
     * @return DataGridIcon
     */
    public function getIconLast();

    /**
     * @param DataGridIcon $iconLast
     */
    public function setIconLast($iconLast);

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
}