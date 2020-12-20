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

namespace SP\Html\DataGrid;

use SP\Html\Assets\IconInterface;

defined('APP_ROOT') || die();


/**
 * Interface DataGridSortInterface para la definicíon de campos de ordenación
 *
 * @package SP\Html\DataGrid
 */
interface DataGridSortInterface
{
    /**
     * @return int
     */
    public function getSortKey(): int;

    /**
     * @param $key int
     */
    public function setSortKey(int $key);

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param $title string
     */
    public function setTitle(string $title);

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param $name string
     */
    public function setName(string $name);

    /**
     * @return string
     */
    public function getClass(): string;

    /**
     * @param $class string
     */
    public function setClass(string $class);

    /**
     * @return IconInterface|null
     */
    public function getIconUp(): ?IconInterface;

    /**
     * @param IconInterface $icon
     */
    public function setIconUp(IconInterface $icon);

    /**
     * @return IconInterface|null
     */
    public function getIconDown(): ?IconInterface;

    /**
     * @param IconInterface $icon
     */
    public function setIconDown(IconInterface $icon);
}