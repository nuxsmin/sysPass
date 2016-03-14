<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mgmt\Categories;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\DataModel\CategoryData;

/**
 * Class CategoryBase
 *
 * @package SP\Mgmt\Categories
 */
abstract class CategoryBase
{
    /** @var CategoryData */
    protected $itemData;

    /**
     * Category constructor.
     *
     * @param CategoryData $itemData
     */
    public function __construct(CategoryData $itemData = null)
    {
        $this->itemData = (!is_null($itemData)) ? $itemData : new CategoryData();
    }

    /**
     * @param CategoryData $itemData
     * @return static
     */
    public static function getItem($itemData = null)
    {
        return new static($itemData);
    }

    /**
     * @return CategoryData
     */
    public function getItemData()
    {
        return ($this->itemData instanceof CategoryData) ? $this->itemData : new CategoryData();
    }

    /**
     * @param $itemData
     * @return $this
     */
    public function setItemData($itemData)
    {
        $this->itemData = $itemData;
        return $this;
    }
}