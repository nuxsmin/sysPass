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

namespace SP\Http;

use SP\Config\ConfigData;

/**
 * Class Client
 *
 * @package SP\Http
 */
final class Client
{
    /**
     * @param ConfigData $configData
     *
     * @return array
     */
    public static function getOptions(ConfigData $configData)
    {
        $options = [
            'timeout' => 10,
            'version' => 1.1
        ];

        if ($configData->isProxyEnabled()) {
            $options['proxy'] = sprintf('tcp://%s:%d', $configData->getProxyServer(), $configData->getProxyPort());

            if (!empty($configData->getProxyUser()) && !empty($configData->getProxyPass())) {
                $options['auth'] = [$configData->getProxyUser(), $configData->getProxyPass()];
            }
        }

        return $options;
    }
}