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

namespace SP\Services\ItemPreset;

use SP\DataModel\ItemPresetData;


/**
 * Class ItemPresetRequest
 *
 * @package SP\Services\ItemPreset
 */
final class ItemPresetRequest
{
    /**
     * @var ItemPresetData
     */
    private $itemPresetData;
    /**
     * @var mixed
     */
    private $data;

    /**
     * ItemPresetRequest constructor.
     *
     * @param ItemPresetData $itemPresetData
     * @param mixed          $data
     */
    public function __construct(ItemPresetData $itemPresetData, $data)
    {
        $this->itemPresetData = $itemPresetData;
        $this->data = $data;
    }

    /**
     * @return ItemPresetData
     */
    public function getItemPresetData(): ItemPresetData
    {
        return $this->itemPresetData;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return ItemPresetData
     */
    public function prepareToPersist()
    {
        $this->itemPresetData->setData(serialize($this->data));

        return $this->itemPresetData;
    }
}