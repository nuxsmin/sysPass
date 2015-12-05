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
 * Interface DataGridSortInterface para la definicíon de campos de ordenación
 *
 * @package SP\Html\DataGrid
 */
interface DataGridSortInterface
{
    /**
     * @return int
     */
    public function getSortKey();

    /**
     * @param $key int
     */
    public function setSortKey($key);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param $title string
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param $name string
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getClass();

    /**
     * @param $class string
     */
    public function setClass($class);

    /**
     * @return DataGridIconInterface
     */
    public function getIconUp();

    /**
     * @param DataGridIconInterface $icon
     */
    public function setIconUp(DataGridIconInterface $icon);

    /**
     * @return DataGridIconInterface
     */
    public function getIconDown();

    /**
     * @param DataGridIconInterface $icon
     */
    public function setIconDown(DataGridIconInterface $icon);
}