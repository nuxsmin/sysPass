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

namespace SP\Mgmt\Files;

use SP\DataModel\FileData;

/**
 * Class FileBase
 *
 * @package SP\Mgmt\Files
 */
abstract class FileBase
{
    /** @var FileData */
    protected $itemData;

    /**
     * Category constructor.
     *
     * @param FileData $itemData
     */
    public function __construct(FileData $itemData = null)
    {
        $this->itemData = (!is_null($itemData)) ? $itemData : new FileData();
    }

    /**
     * @param FileData $itemData
     * @return static
     */
    public static function getItem($itemData = null)
    {
        return new static($itemData);
    }

    /**
     * @return FileData
     */
    public function getItemData()
    {
        return $this->itemData;
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