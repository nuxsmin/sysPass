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

namespace SP\Html\DataGrid;

use SP\Html\Assets\IconInterface;

defined('APP_ROOT') || die();

/**
 * Class DataGridSort para la definición de campos de ordenación
 *
 * @package SP\Html\DataGrid
 */
final class DataGridSort implements DataGridSortInterface
{
    /**
     * @var int
     */
    private $sortKey = 0;
    /**
     * @var string
     */
    private $title = '';
    /**
     * @var string
     */
    private $name = '';
    /**
     * @var array
     */
    private $class = array();
    /**
     * @var IconInterface
     */
    private $iconUp;
    /**
     * @var IconInterface
     */
    private $iconDown;

    /**
     * @return int
     */
    public function getSortKey()
    {
        return $this->sortKey;
    }

    /**
     * @param $key int
     *
     * @return $this
     */
    public function setSortKey($key)
    {
        $this->sortKey = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title string
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name string
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return implode(' ', $this->class);
    }

    /**
     * @param $class string
     *
     * @return $this
     */
    public function setClass($class)
    {
        $this->class[] = $class;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconUp()
    {
        return $this->iconUp;
    }

    /**
     * @param IconInterface $icon
     *
     * @return $this
     */
    public function setIconUp(IconInterface $icon)
    {
        $this->iconUp = $icon;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconDown()
    {
        return $this->iconDown;
    }

    /**
     * @param IconInterface $icon
     *
     * @return $this
     */
    public function setIconDown(IconInterface $icon)
    {
        $this->iconDown = $icon;

        return $this;
    }
}