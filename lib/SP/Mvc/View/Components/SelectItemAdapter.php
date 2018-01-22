<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Mvc\View\Components;

use RuntimeException;
use SP\DataModel\DataModelInterface;
use SP\Util\Json;

/**
 * Class SelectItemAdapter
 *
 * @package SP\Mvc\View\Components
 */
class SelectItemAdapter implements ItemAdapterInterface
{
    /**
     * @var array
     */
    protected $items;

    /**
     * SelectItemAdapter constructor.
     *
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * Returns a JSON like collection of items for a select component
     *
     * @return string
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getJsonItemsFromModel()
    {
        $out = [];

        foreach ($this->items as $item) {
            if (!$item instanceof DataModelInterface) {
                throw new RuntimeException(__u('Tipo de objeto incorrecto'));
            }

            $out[] = ['id' => $item->getId(), 'name' => $item->getName()];
        }

        return Json::getJson($out);
    }

    /**
     * Returns a collection of items for a select component
     *
     * @return array
     */
    public function getItemsFromArray()
    {
        $out = [];

        foreach ($this->items as $key => $value) {
            $out[] = new SelectItem($key, $value);
        }

        return $out;
    }

    /**
     * Returns a collection of items for a select component
     *
     * @return string
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getJsonItemsFromArray()
    {
        $out = [];

        foreach ($this->items as $key => $value) {
            $out[] = ['id' => $key, 'name' => $value];
        }

        return Json::getJson($out);
    }

    /**
     * Returns a collection of items for a select component and set selected ones from an array
     *
     * @param array $selected
     * @return SelectItem[]
     */
    public function getItemsFromModelSelected(array $selected)
    {
        $items = $this->getItemsFromModel();

        foreach ($items as $item) {
            if ($selected !== null && in_array($item->getId(), $selected, false)) {
                $item->setSelected(true);
            }
        }

        return $items;
    }

    /**
     * Returns a collection of items for a select component
     *
     * @return SelectItem[]
     */
    public function getItemsFromModel()
    {
        $out = [];

        foreach ($this->items as $item) {
            if (!$item instanceof DataModelInterface) {
                throw new RuntimeException(__u('Tipo de objeto incorrecto'));
            }

            $out[] = new SelectItem($item->getId(), $item->getName(), $item);
        }

        return $out;
    }

    /**
     * Returns a collection of items for a select component and set selected ones from an array
     *
     * @param array $selected
     * @return SelectItem[]
     */
    public function getItemsFromArraySelected(array $selected)
    {
        $items = $this->getItemsFromArray();

        foreach ($items as $item) {
            if ($selected !== null && in_array($item->getId(), $selected, false)) {
                $item->setSelected(true);
            }
        }

        return $items;
    }
}