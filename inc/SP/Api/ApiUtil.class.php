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

namespace SP\Api;

defined('APP_ROOT') || die();

use SP\Core\Exceptions\SPException;
use SP\DataModel\TrackData;
use SP\Mgmt\Tracks\Track;
use SP\Util\Util;

/**
 * Class ApiUtil
 *
 * @package SP\Api
 */
class ApiUtil
{
    /**
     * Añadir un seguimiento
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function addTracking()
    {
        try {
            $TrackData = new TrackData();
            $TrackData->setTrackSource('API');
            $TrackData->setTrackIp(Util::getClientAddress());

            Track::getItem($TrackData)->add();
        } catch (SPException $e) {
            throw new SPException(SPException::SP_WARNING, __('Error interno', false), '', -32601);
        }
    }
}