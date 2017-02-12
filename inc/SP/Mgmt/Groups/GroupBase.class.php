<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mgmt\Groups;

use SP\DataModel\GroupData;
use SP\Mgmt\ItemBase;

/**
 * Class GroupBase
 *
 * @package SP\Mgmt\Groups
 */
abstract class GroupBase extends ItemBase
{
    /** @var GroupData */
    protected $itemData;

    /**
     * Category constructor.
     *
     * @param GroupData $itemData
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct($itemData = null)
    {
        if (!$this->dataModel) {
            $this->setDataModel(GroupData::class);
        }

        parent::__construct($itemData);
    }

    /**
     * @return GroupData
     */
    public function getItemData()
    {
        return parent::getItemData();
    }
}