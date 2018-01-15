<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services;

use SP\DataModel\DataModelInterface;
use SP\Repositories\RepositoryItemInterface;

/**
 * Trait ServiceItemTrait
 *
 * @package SP\Services
 */
trait ServiceItemTrait
{
    /**
     * Devolver los elementos para un campo select
     *
     * @param RepositoryItemInterface $repositoryItem
     * @return array
     */
    public function getItemsForSelect(RepositoryItemInterface $repositoryItem)
    {
        $items = [];

        /** @var DataModelInterface $item */
        foreach ($repositoryItem->getAll() as $item) {
            $obj = new \stdClass();
            $obj->id = (int)$item->getId();
            $obj->name = $item->getName();

            $items[] = $obj;
        }

        return $items;
    }

    /**
     * Returns all the items mapping fields for a select type element (id and name fields)
     */
    abstract public function getAllItemsForSelect();
}