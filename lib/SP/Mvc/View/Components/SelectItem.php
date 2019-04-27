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
 * Class SelectItem
 *
 * @package SP\Mvc\View\Components
 */
final class SelectItem
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var mixed
     */
    protected $item;
    /**
     * @var bool
     */
    protected $selected = false;
    /**
     * @var bool
     */
    protected $skip = false;

    /**
     * SelectItem constructor.
     *
     * @param int    $id
     * @param string $name
     * @param null   $item
     */
    public function __construct($id, $name, $item = null)
    {
        $this->id = is_numeric($id) ? (int)$id : $id;
        $this->name = (string)$name;
        $this->item = $item;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isSelected()
    {
        return $this->selected;
    }

    /**
     * @param bool $selected
     */
    public function setSelected($selected)
    {
        $this->selected = (bool)$selected;
    }

    /**
     * @param $property
     *
     * @return mixed
     */
    public function getItemProperty($property)
    {
        return null !== $this->item && isset($this->item->{$property}) ? $this->item->{$property} : null;
    }

    /**
     * @return bool
     */
    public function isSkip()
    {
        return $this->skip;
    }

    /**
     * @param bool $skip
     */
    public function setSkip($skip)
    {
        $this->skip = (bool)$skip;
    }
}