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

use RuntimeException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\DataModelInterface;
use SP\Http\Json;

/**
 * Class SelectItemAdapter
 *
 * @package SP\Mvc\View\Components
 */
final class SelectItemAdapter implements ItemAdapterInterface
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
     * @param array $items
     *
     * @return static
     */
    public static function factory(array $items)
    {
        return new static($items);
    }

    /**
     * Returns an array of ids from the given array of objects
     *
     * @param array $items
     *
     * @return array
     */
    public static function getIdFromArrayOfObjects(array $items)
    {
        $ids = [];

        foreach ($items as $item) {
            if (is_object($item) && null !== $item->id) {
                $ids[] = $item->id;
            }
        }

        return $ids;
    }

    /**
     * Returns a JSON like collection of items for a select component
     *
     * @return string
     * @throws SPException
     */
    public function getJsonItemsFromModel()
    {
        $out = [];

        foreach ($this->items as $item) {
            if (!$item instanceof DataModelInterface) {
                throw new RuntimeException(__u('Wrong object type'));
            }

            $out[] = ['id' => $item->getId(), 'name' => $item->getName()];
        }

        return Json::getJson($out);
    }

    /**
     * Returns a collection of items for a select component
     *
     * @return string
     * @throws SPException
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
     * @param null  $skip
     *
     * @return SelectItem[]
     */
    public function getItemsFromModelSelected(array $selected, $skip = null)
    {
        $items = $this->getItemsFromModel();

        foreach ($items as $item) {
            if ($skip !== null && $item->getId() === $skip) {
                $item->setSkip(true);
            }

            if (in_array($item->getId(), $selected, false)) {
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
                throw new RuntimeException(__u('Wrong object type'));
            }

            $out[] = new SelectItem($item->getId(), $item->getName(), $item);
        }

        return $out;
    }

    /**
     * Returns a collection of items for a select component and set selected ones from an array
     *
     * @param array $selected
     * @param bool  $useValueAsKey
     *
     * @return SelectItem[]
     */
    public function getItemsFromArraySelected(array $selected, $useValueAsKey = false)
    {
        $items = $this->getItemsFromArray();

        foreach ($items as $item) {
            $value = $useValueAsKey ? $item->getName() : $item->getId();

            if (in_array($value, $selected, false)) {
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
    public function getItemsFromArray()
    {
        $out = [];

        foreach ($this->items as $key => $value) {
            $out[] = new SelectItem($key, $value);
        }

        return $out;
    }
}