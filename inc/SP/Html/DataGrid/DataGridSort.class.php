<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Html\DataGrid;

use SP\Html\Assets\IconInterface;

defined('APP_ROOT') || die();

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
    /** @var IconInterface */
    private $_iconUp;
    /** @var IconInterface */
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
     * @return $this
     */
    public function setSortKey($key)
    {
        $this->_sortKey = $key;

        return $this;
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
     * @return $this
     */
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
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
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
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
     * @return $this
     */
    public function setClass($class)
    {
        $this->_class[] = $class;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconUp()
    {
        return $this->_iconUp;
    }

    /**
     * @param IconInterface $icon
     * @return $this
     */
    public function setIconUp(IconInterface $icon)
    {
        $this->_iconUp = $icon;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconDown()
    {
        return $this->_iconDown;
    }

    /**
     * @param IconInterface $icon
     * @return $this
     */
    public function setIconDown(IconInterface $icon)
    {
        $this->_iconDown = $icon;

        return $this;
    }
}