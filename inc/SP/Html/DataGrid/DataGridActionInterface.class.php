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
 * Interface DataGridActionInterface
 *
 * @package SP\Html\DataGrid
 */
interface DataGridActionInterface
{
    /**
     * @param $name string
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param int $id
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param $title string
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param $function string
     */
    public function setOnClickFunction($function);

    /**
     * @param $args string
     */
    public function setOnClickArgs($args);

    /**
     * @return string
     */
    public function getOnClick();

    /**
     * @param $icon string
     */
    public function setIcon($icon);

    /**
     * @return string
     */
    public function getIcon();

    /**
     * @param $skip bool
     */
    public function setSkip($skip);

    /**
     * @return bool
     */
    public function isSkip();

    /**
     * @param $delete bool
     */
    public function setIsDelete($delete);

    /**
     * @return bool
     */
    public function isDelete();

    /**
     * @param $new bool
     */
    public function setIsNew($new);

    /**
     * @return bool
     */
    public function isNew();

    /**
     * @param $rowSource string
     */
    public function setFilterRowSource($rowSource);

    /**
     * @return string
     */
    public function getFilterRowSource();
}
