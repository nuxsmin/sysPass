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

namespace SP\Mgmt\Tracks;

defined('APP_ROOT') || die();

use SP\DataModel\TrackData;
use SP\Mgmt\ItemBase;

/**
 * Class TrackBase
 *
 * @package SP\Mgmt\Tracks
 */
abstract class TrackBase extends ItemBase
{
    /** @var TrackData */
    protected $itemData;

    /**
     * Track constructor.
     *
     * @param $itemData
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct($itemData = null)
    {
        if (!$this->dataModel) {
            $this->setDataModel(TrackData::class);
        }

        parent::__construct($itemData);
    }

    /**
     * Devolver los datos del elemento
     * @return TrackData
     */
    public function getItemData()
    {
        return parent::getItemData();
    }
}