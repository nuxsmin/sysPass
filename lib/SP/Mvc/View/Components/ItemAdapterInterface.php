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

namespace SP\Mvc\View\Components;

/**
 * Interface ItemAdapterInterface
 *
 * @package SP\Mvc\View\Components
 */
interface ItemAdapterInterface
{
    /**
     * Returns a collection of items for a select component
     *
     * @return array
     */
    public function getItemsFromModel();

    /**
     * Returns a JSON like collection of items for a select component
     *
     * @return string
     */
    public function getJsonItemsFromModel();

    /**
     * Returns a collection of items for a select component
     *
     * @return array
     */
    public function getItemsFromArray();

    /**
     * Returns a collection of items for a select component
     *
     * @return string
     */
    public function getJsonItemsFromArray();
}