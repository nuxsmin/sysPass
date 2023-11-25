<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use function SP\__;

defined('APP_ROOT') || die();

/**
 * Class DataGridIconBase para crear los iconos de la matriz
 *
 * @package SP\Html\DataGrid
 */
abstract class IconBase implements IconInterface
{
    /**
     * Clases CSS del icono
     */
    protected ?array $class = null;

    public function __construct(
        protected string  $icon,
        string|array|null $class = null,
        protected ?string $title = null
    ) {
        if ($class) {
            $this->setClass($class);
        }
    }

    private function setClass(string|array $class): void
    {
        if (is_array($class)) {
            $this->class = $class;
        } else {
            $this->class[] = $class;
        }
    }

    public function getTitle(): ?string
    {
        if ($this->title) {
            return __($this->title);
        }

        return null;
    }

    public function getClass(): ?string
    {
        if ($this->class) {
            return implode(' ', $this->class);
        }

        return null;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function mutate(?string $icon = null, string|array|null $class = null, ?string $title = null): IconInterface
    {
        $clone = clone $this;

        if ($icon) {
            $clone->icon = $icon;
        }

        if ($class) {
            $clone->setClass($class);
        }

        if ($title) {
            $clone->title = $title;
        }

        return $clone;
    }
}
