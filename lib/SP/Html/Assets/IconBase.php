<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
     */
    protected string $icon;

    /**
     * Título del icono
     */
    protected ?string $title = null;
    /**
     * Clases CSS del icono
     */
    protected ?array $class = null;

    public function __construct(
        string  $icon,
        ?string $class = null,
        ?string $title = null
    )
    {
        $this->setIcon($icon);
        $this->setClass($class);
        $this->setTitle($title);
    }

    public function getTitle(): ?string
    {
        if ($this->title) {
            return __($this->title);
        }

        return null;
    }

    public function setTitle(?string $title): IconBase
    {
        $this->title = $title;

        return $this;
    }

    public function getClass(): ?string
    {
        if ($this->class) {
            return implode(' ', $this->class);
        }

        return null;
    }

    public function setClass(?string $class): IconBase
    {
        if ($class) {
            $this->class[] = $class;
        }

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): IconBase
    {
        $this->icon = $icon;

        return $this;
    }
}