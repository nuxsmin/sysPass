<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Util;


use SP\Bootstrap;
use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Http\Uri;

/**
 * Class Link
 *
 * @package SP\Util
 */
final class Link
{
    /**
     * @param int        $itemId
     * @param int        $actionId
     * @param ConfigData $configData
     *
     * @param bool  $useUI
     *
     * @return string
     */
    public static function getDeepLink(int $itemId, int $actionId, ConfigData $configData, bool $useUI = false)
    {
        $route = Acl::getActionRoute($actionId) . '/' . $itemId;

        if ($useUI) {
            $baseUrl = ($configData->getApplicationUrl() ?: Bootstrap::$WEBURI) . '/index.php';
        } else {
            $baseUrl = ($configData->getApplicationUrl() ?: Bootstrap::$WEBURI) . Bootstrap::$SUBURI;
        }

        $uri = new Uri($baseUrl);
        $uri->addParam('r', $route);

        return $uri->getUriSigned($configData->getPasswordSalt());
    }
}