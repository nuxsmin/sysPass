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

namespace SP\Mvc\View\Components;

use JsonSerializable;

/**
 * Class SelectItem
 *
 * @package SP\Mvc\View\Components
 */
final class SelectItem implements JsonSerializable
{
    protected $id;
    protected string $name;
    protected $item;
    protected bool $selected = false;
    protected bool $skip = false;

    /**
     * SelectItem constructor.
     *
     * @param int|string $id
     * @param string     $name
     * @param mixed      $item
     */
    public function __construct($id, string $name, $item = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->item = $item;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
    }

    /**
     * @return mixed
     */
    public function getItemProperty(string $property)
    {
        return null !== $this->item && isset($this->item->{$property})
            ? $this->item->{$property}
            : null;
    }

    public function isSkip(): bool
    {
        return $this->skip;
    }

    public function setSkip(bool $skip): void
    {
        $this->skip = $skip;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}