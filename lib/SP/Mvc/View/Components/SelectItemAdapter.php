<?php
declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Common\Models\ItemWithIdAndNameModel;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Services\JsonResponse;

use function SP\__u;

/**
 * Class SelectItemAdapter
 */
final readonly class SelectItemAdapter implements ItemAdapterInterface
{

    public function __construct(protected array $items)
    {
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
        return array_map(
            static fn(object $item) => $item->id,
            array_filter($items, static fn(mixed $item) => is_object($item) && isset($item->id))
        );
    }

    /**
     * Returns a JSON like collection of items for a select component
     *
     * @throws SPException
     */
    public function getJsonItemsFromModel(): string
    {
        return JsonResponse::buildJsonFrom(
            array_map(
                static fn(ItemWithIdAndNameModel $item) => ['id' => $item->getId(), 'name' => $item->getName()],
                array_filter($this->items, static fn(mixed $item) => $item instanceof ItemWithIdAndNameModel)
            )
        );
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

        return JsonResponse::buildJsonFrom($out);
    }

    /**
     * Returns a collection of items for a select component and set selected ones from an array
     *
     * @param array $selected
     * @param string|int|null $skip
     *
     * @return SelectItem[]
     */
    public function getItemsFromModelSelected(array $selected, mixed $skip = null): array
    {
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
                if (!$item instanceof ItemWithIdAndNameModel) {
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
