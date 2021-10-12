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
    private int $sortKey = 0;
    /**
     * @var string
     */
    private string $title = '';
    /**
     * @var string
     */
    private string $name = '';
    /**
     * @var array
     */
    private array $class = [];
    /**
     * @var IconInterface
     */
    private IconInterface $iconUp;
    /**
     * @var IconInterface
     */
    private IconInterface $iconDown;

    /**
     * @return int
     */
    public function getSortKey(): int
    {
        return $this->sortKey;
    }

    /**
     * @param $key int
     *
     * @return $this
     */
    public function setSortKey(int $key): DataGridSortInterface
    {
        $this->sortKey = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param $title string
     *
     * @return $this
     */
    public function setTitle(string $title): DataGridSortInterface
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param $name string
     *
     * @return $this
     */
    public function setName(string $name): DataGridSortInterface
    {
        $this->name = $name;

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
     * @param $class string
     *
     * @return $this
     */
    public function setClass(string $class): DataGridSortInterface
    {
        $this->class[] = $class;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconUp(): ?IconInterface
    {
        return $this->iconUp;
    }

    /**
     * @param IconInterface $icon
     *
     * @return $this
     */
    public function setIconUp(IconInterface $icon): DataGridSortInterface
    {
        $this->iconUp = $icon;

        return $this;
    }

    /**
     * @return IconInterface
     */
    public function getIconDown(): ?IconInterface
    {
        return $this->iconDown;
    }

    /**
     * @param IconInterface $icon
     *
     * @return $this
     */
    public function setIconDown(IconInterface $icon): DataGridSortInterface
    {
        $this->iconDown = $icon;

        return $this;
    }
}