<?php
declare(strict_types=1);
/**
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

namespace SP\Html\DataGrid;

use SP\Html\Assets\IconInterface;

/**
 * Class DataGridSort para la definición de campos de ordenación
 *
 * @package SP\Html\DataGrid
 */
final class DataGridSort implements DataGridSortInterface
{
    private int    $sortKey = 0;
    private string $title   = '';
    private string $name    = '';
    private array  $class   = [];
    private IconInterface $iconUp;
    private IconInterface $iconDown;

    public function getSortKey(): int
    {
        return $this->sortKey;
    }

    public function setSortKey(int $key): DataGridSortInterface
    {
        $this->sortKey = $key;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): DataGridSortInterface
    {
        $this->title = $title;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): DataGridSortInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getClass(): string
    {
        return implode(' ', $this->class);
    }

    public function setClass(string $class): DataGridSortInterface
    {
        $this->class[] = $class;

        return $this;
    }

    public function getIconUp(): ?IconInterface
    {
        return $this->iconUp;
    }

    public function setIconUp(IconInterface $icon): DataGridSortInterface
    {
        $this->iconUp = $icon;

        return $this;
    }

    public function getIconDown(): ?IconInterface
    {
        return $this->iconDown;
    }

    public function setIconDown(IconInterface $icon): DataGridSortInterface
    {
        $this->iconDown = $icon;

        return $this;
    }
}
