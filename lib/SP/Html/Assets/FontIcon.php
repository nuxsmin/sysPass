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

namespace SP\Html\Assets;

defined('APP_ROOT') || die();

/**
 * Class FontIcon para crear los iconos de la matriz
 *
 * @package SP\Html\Assets
 */
final class FontIcon extends IconBase
{
    /**
     * @param string $icon
     * @param string $class
     * @param string $title
     */
    public function __construct($icon, $class = null, $title = null)
    {
        $this->setIcon($icon);
        $this->setClass($class);
        $this->setTitle($title);
    }

    /**
     * Devolver la clase del icono adaptada para un botón
     *
     * @return string
     */
    public function getClassButton()
    {
        return str_replace('-text--', '--', $this->getClass());
    }
}