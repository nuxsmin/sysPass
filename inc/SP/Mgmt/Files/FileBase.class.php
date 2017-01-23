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

namespace SP\Mgmt\Files;

use SP\DataModel\FileData;
use SP\Mgmt\ItemBase;

/**
 * Class FileBase
 *
 * @package SP\Mgmt\Files
 */
abstract class FileBase extends ItemBase
{
    /** @var FileData */
    protected $itemData;

    /**
     * Category constructor.
     *
     * @param FileData $itemData
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct($itemData = null)
    {
        if (!$this->dataModel) {
            $this->setDataModel(FileData::class);
        }

        parent::__construct($itemData);
    }

    /**
     * Devolver los datos del elemento
     * @return FileData
     */
    public function getItemData()
    {
        return parent::getItemData();
    }
}