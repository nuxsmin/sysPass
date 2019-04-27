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

namespace SP\Services\Upgrade;

use SP\Config\ConfigData;

/**
 * Interface UpgradeInterface
 *
 * @package SP\Services\Upgrade
 */
interface UpgradeInterface
{
    /**
     * Performs the upgrading process
     *
     * @param            $version
     * @param ConfigData $configData
     */
    public function upgrade($version, ConfigData $configData);

    /**
     * Check if it needs to be upgraded
     *
     * @param $version
     *
     * @return bool
     */
    public static function needsUpgrade($version);
}