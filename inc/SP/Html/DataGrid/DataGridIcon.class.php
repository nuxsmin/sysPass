<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

/**
 * Class DataGridIcon para crear los iconos de la matriz
 *
 * @package SP\Html\DataGrid
 */
class DataGridIcon extends DataGridIconBase
{
    /**
     * @param string $icon
     * @param string $image
     * @param string $class
     * @param string $title
     */
    public function __construct($icon, $image, $class = null, $title = null)
    {
        $this->setIcon($icon);
        $this->setImage($image);
        $this->setClass($class);
        $this->setTitle($title);
    }
}