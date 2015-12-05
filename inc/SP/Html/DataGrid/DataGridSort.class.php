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
 * Class DataGridSort para la definición de campos de ordenación
 *
 * @package SP\Html\DataGrid
 */
class DataGridSort implements DataGridSortInterface
{
    /** @var int */
    private $_sortKey = 0;
    /** @var string */
    private $_title = '';
    /** @var string */
    private $_name = '';
    /** @var array */
    private $_class = array();
    /** @var DataGridIconInterface */
    private $_iconUp;
    /** @var DataGridIconInterface */
    private $_iconDown;

    /**
     * @return int
     */
    public function getSortKey()
    {
        return $this->_sortKey;
    }

    /**
     * @param $key int
     */
    public function setSortKey($key)
    {
        $this->_sortKey = $key;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param $title string
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $name string
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return implode(' ', $this->_class);
    }

    /**
     * @param $class string
     */
    public function setClass($class)
    {
        $this->_class[] = $class;
    }

    /**
     * @return DataGridIconInterface
     */
    public function getIconUp()
    {
        return $this->_iconUp;
    }

    /**
     * @param DataGridIconInterface $icon
     */
    public function setIconUp(DataGridIconInterface $icon)
    {
        $this->_iconUp = $icon;
    }

    /**
     * @return DataGridIconInterface
     */
    public function getIconDown()
    {
        return $this->_iconDown;
    }

    /**
     * @param DataGridIconInterface $icon
     */
    public function setIconDown(DataGridIconInterface $icon)
    {
        $this->_iconDown = $icon;
    }
}