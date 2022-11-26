<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mvc\View\Components;

use RuntimeException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Common\Adapters\DataModelInterface;
use SP\Http\Json;
use function SP\__u;

/**
 * Class SelectItemAdapter
 *
 * @package SP\Mvc\View\Components
 */
final class SelectItemAdapter implements ItemAdapterInterface
{
    protected array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function factory(array $items): SelectItemAdapter
    {
        return new SelectItemAdapter($items);
    }

    /**
     * Returns an array of ids from the given array of objects
     */
    public static function getIdFromArrayOfObjects(array $items): array
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
     * @throws SPException
     */
    public function getJsonItemsFromModel(): string
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
     * @throws SPException
     */
    public function getJsonItemsFromArray(): string
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
     * @param  array  $selected
     * @param  string|int|null  $skip
     *
     * @return SelectItem[]
     */
    public function getItemsFromModelSelected(
        array $selected,
        mixed $skip = null
    ): array {
        $items = $this->getItemsFromModel();

        array_walk(
            $items,
            static function (SelectItem $item) use ($selected, $skip) {
                $item->setSkip($item->getId() === $skip);
                /** @noinspection TypeUnsafeArraySearchInspection */
                $item->setSelected(in_array($item->getId(), $selected));
            }
        );

        return $items;
    }

    /**
     * Returns a collection of items for a select component
     *
     * @return SelectItem[]
     */
    public function getItemsFromModel(): array
    {
        return array_map(
            static function ($item) {
                if (!$item instanceof DataModelInterface) {
                    throw new RuntimeException(__u('Wrong object type'));
                }

                return new SelectItem($item->getId(), $item->getName(), $item);
            },
            $this->items
        );
    }

    /**
     * Returns a collection of items for a select component and set selected ones from an array
     *
     * @return SelectItem[]
     */
    public function getItemsFromArraySelected(array $selected, bool $useValueAsKey = false): array
    {
        $items = $this->getItemsFromArray();

        array_walk(
            $items,
            static function (SelectItem $item) use ($selected, $useValueAsKey) {
                $value = $useValueAsKey ? $item->getName() : $item->getId();

                /** @noinspection TypeUnsafeArraySearchInspection */
                $item->setSelected(in_array($value, $selected));
            }
        );

        return $items;
    }

    /**
     * Returns a collection of items for a select component
     *
     * @return SelectItem[]
     */
    public function getItemsFromArray(): array
    {
        return array_map(
            static fn($key, $value) => new SelectItem($key, $value),
            array_keys($this->items),
            array_values($this->items)
        );
    }
}
