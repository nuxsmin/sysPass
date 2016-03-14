<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

namespace SP\Mgmt\Profiles;

use SP\DataModel\ProfileData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase ProfileBase para la definición de perfiles de acceso de usuarios
 *
 * @package SP
 */
abstract class ProfileBase
{
    /** @var ProfileData */
    protected $itemData;

    /**
     * Category constructor.
     *
     * @param ProfileData $itemData
     */
    public function __construct(ProfileData $itemData = null)
    {
        $this->itemData = (!is_null($itemData)) ? $itemData : new ProfileData();
    }

    /**
     * @param ProfileData $itemData
     * @return static
     */
    public static function getItem($itemData = null)
    {
        return new static($itemData);
    }

    /**
     * @return ProfileData
     */
    public function getItemData()
    {
        return $this->itemData;
    }

    /**
     * @param ProfileData $itemData
     * @return $this
     */
    public function setItemData($itemData)
    {
        $this->itemData = $itemData;
        return $this;
    }
}