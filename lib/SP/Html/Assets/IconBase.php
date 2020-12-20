<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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
 * Class DataGridIconBase para crear los iconos de la matriz
 *
 * @package SP\Html\DataGrid
 */
abstract class IconBase implements IconInterface
{
    /**
     * El nombre del icono o imagen a utilizar
     *
     * @var string
     */
    protected $icon = '';

    /**
     * Título del icono
     *
     * @var string
     */
    protected $title = '';
    /**
     * Clases CSS del icono
     *
     * @var array
     */
    protected $class = [];

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return __($this->title);
    }

    /**
     * @param string|null $title
     *
     * @return $this
     */
    public function setTitle(?string $title): IconBase
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return implode(' ', $this->class);
    }

    /**
     * @param string|null $class
     *
     * @return $this
     */
    public function setClass(?string $class): IconBase
    {
        if ($class) {
            $this->class[] = $class;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon(string $icon): IconBase
    {
        $this->icon = $icon;
        return $this;
    }
}